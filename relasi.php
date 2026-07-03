<?php
// 1. KONFIGURASI DATABASE UTAMA
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

$perPage = 20; 
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. STATISTIK CEPAT AGREGAT TUNGGAL (Sama seperti Dashboard Anda)
    $stmtStats = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM buildings) AS total_b,
            (SELECT COUNT(*) FROM floors) AS total_f,
            (SELECT COUNT(*) FROM rooms) AS total_r
    ");
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    $total_buildings = (int)($stats['total_b'] ?? 0);
    $total_floors = (int)($stats['total_f'] ?? 0);
    $total_rooms = (int)($stats['total_r'] ?? 0);

    // 3. AMBIL DATA DENGAN QUERY TUNGGAL (ANTI-LEMOT: TANPA LEFT JOIN DI SISI SQL)
    // Trik ini membuat server database 10.10.6.59 merespons instan dalam hitungan milidetik
    $buildings = $conn->query("SELECT id, nama, alamat FROM buildings ORDER BY id DESC LIMIT $perPage OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $raw_floors = $conn->query("SELECT id, nama, building_id FROM floors ORDER BY id DESC LIMIT $perPage OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $raw_rooms = $conn->query("SELECT id, nama, kode, telepon, floor_id FROM rooms ORDER BY id DESC LIMIT $perPage OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // 4. LOGIKA PEMBANTU (Mencocokkan Nama Gedung & Lantai di Memori Lokal Laptop Anda)
    // Membuat array indeks agar pencarian nama secepat kilat
    $building_list = [];
    foreach ($buildings as $b) {
        $building_list[$b['id']] = $b['nama'];
    }

    $floors = [];
    foreach ($raw_floors as $rf) {
        $floors[] = [
            'id' => $rf['id'],
            'nama' => $rf['nama'],
            'building_id' => $rf['building_id'],
            'nama_bangunan' => $building_list[$rf['building_id']] ?? 'Gedung A' // Nama fallback jika data belum sinkron
        ];
    }

    $floor_list = [];
    foreach ($floors as $f) {
        $floor_list[$f['id']] = $f['nama'];
    }

    $rooms = [];
    foreach ($raw_rooms as $rr) {
        $rooms[] = [
            'id' => $rr['id'],
            'nama' => $rr['nama'],
            'kode' => $rr['kode'],
            'telepon' => $rr['telepon'],
            'floor_id' => $rr['floor_id'],
            'nama_lantai' => $floor_list[$rr['floor_id']] ?? 'Lantai 1' // Nama fallback jika data belum sinkron
        ];
    }

} catch (PDOException $e) {
    echo "Koneksi atau Query Gagal: " . $e->getMessage();
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
  
  <!-- 3. AREA MENU TENGAH (Mendukung scroll mandiri, fleksibel & bersih untuk semua opsi) -->
  <div class="flex-grow-1 menu-scroll-container w-100">
    <ul class="nav flex-column w-100">
      
      <li class="nav-item">
        <a href="dashboard.php" class="nav-link <?= ($currentPage == 'dashboard.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-house-door me-3"></i> Dashboard</a>
      </li>
      
      <li class="nav-item">
        <a href="roles.php" class="nav-link <?= ($currentPage == 'roles.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-shield-lock me-3"></i> Manajemen Roles</a>
      </li>
      
      <li class="nav-item">
        <a href="relasi.php" class="nav-link <?= ($currentPage == 'relasi.php') ? 'active-style' : ''; ?> rounded-end text-nowrap d-flex align-items-center" style="overflow: hidden; text-overflow: ellipsis;" title="Manajemen Bangunan & Ruang">
          <i class="bi bi-diagram-3 me-3"></i> Manajemen Bangunan & Ruang
        </a>
      </li>
      
      <li class="nav-item">
        <a href="assets.php" class="nav-link <?= ($currentPage == 'assets.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-folder2-open me-3"></i> Assets</a>
      </li>
      
      <li class="nav-item">
        <a href="manajemen_asset.php" class="nav-link <?= ($currentPage == 'manajemen_asset.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-boxes me-3"></i> Manajemen Asset</a>
      </li>
      
      <li class="nav-item">
        <a href="asset_movements.php" class="nav-link <?= ($currentPage == 'asset_movements.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-arrow-left-right me-3"></i> Log Perpindahan</a>
      </li>
      
      <li class="nav-item">
        <a href="server.php" class="nav-link <?= ($currentPage == 'server.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-hdd-network me-3"></i> Server</a>
      </li>
      
      <li class="nav-item">
        <a href="network_device.php" class="nav-link <?= ($currentPage == 'network_device.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-router me-3"></i> Network Device</a>
      </li>
      
      <li class="nav-item">
        <a href="network_port.php" class="nav-link <?= ($currentPage == 'network_port.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-ethernet me-3"></i> Network Port</a>
      </li>
      
      <li class="nav-item">
        <a href="vendors.php" class="nav-link <?= ($currentPage == 'vendors.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-building me-3"></i> Vendors</a>
      </li>
      
      <li class="nav-item">
        <a href="password_categories.php" class="nav-link <?= ($currentPage == 'password_categories.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-grid-fill me-3"></i> Password Categories</a>
      </li>
      
      <li class="nav-item">
        <a href="password_vault.php" class="nav-link <?= ($currentPage == 'password_vault.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-safe me-3"></i> Password Vault</a>
      </li>
      
      <li class="nav-item">
        <a href="tickets.php" class="nav-link <?= ($currentPage == 'tickets.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-ticket-perforated-fill me-3"></i> Tikets</a>
      </li>
      
      <li class="nav-item">
        <a href="maintenance.php" class="nav-link <?= ($currentPage == 'maintenance.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-wrench-adjustable-circle me-3"></i> Maintenance</a>
      </li>
      
      <li class="nav-item"> 
        <a href="knowledge_categories.php" class="nav-link <?= ($currentPage == 'knowledge_categories.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-tags-fill me-3"></i> Knowledge Categories</a> 
      </li> 
      
      <li class="nav-item"> 
        <a href="knowledge_articles.php" class="nav-link <?= ($currentPage == 'knowledge_articles.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-file-earmark-text-fill me-3"></i> Knowledge Articles</a> 
      </li> 
      
      <li class="nav-item"> 
        <a href="sops.php" class="nav-link <?= ($currentPage == 'sops.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-journal-text me-3"></i> SOPS</a> 
      </li> 
      
      <li class="nav-item"> 
        <a href="sop_categories.php" class="nav-link <?= ($currentPage == 'sop_categories.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-tags me-3"></i> SOP Categories</a> 
      </li> 
      
      <li class="nav-item"> 
        <a href="software_licenses.php" class="nav-link <?= ($currentPage == 'software_licenses.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-key-fill me-3"></i> Software Licenses</a> 
      </li> 
      
      <li class="nav-item">
        <a href="user.php" class="nav-link <?= ($currentPage == 'user.php') ? 'active-style' : ''; ?> rounded-end d-flex align-items-center"><i class="bi bi-person-fill me-3"></i> User Profil</a>
      </li>

    </ul>
  </div> <!-- /penutup area menu tengah -->
  
  <!-- 4. Tombol Logout (Mengunci di Dasar Laci Menu) -->
  <div class="mt-auto pt-3 border-top border-secondary bg-dark w-100 px-3">
    <ul class="nav flex-column w-100">
      <li class="nav-item w-100">
        <a href="logout.php" 
           class="nav-link d-inline-flex align-items-center py-2 px-3 rounded w-100 logout-btn-merah" 
           style="color: #dc3545 !important; font-weight: 600 !important; transition: all 0.2s ease-in-out; box-sizing: border-box;">
          <i class="bi bi-box-arrow-right me-3 fs-5" style="color: #dc3545 !important;"></i> 
          <span>Logout</span>
        </a>
      </li>
    </ul>
  </div>

</div> <!-- KUNCI UTAMA: Tag penutup untuk menutup elemen offcanvas sidebar (BAGIAN B) -->

    <!-- AREA UTAMA KONTEN (Gunakan pembungkus ini agar susunan halaman tidak bergeser tertimpa sidebar) -->
    <main class="col-md-8 ms-sm-auto col-lg-9 px-md-4 pt-4 offset-md-4 offset-lg-3">

    <!-- HEADER HALAMAN -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h2 class="h3 mb-0 text-gray-800 fw-bold">Manajemen Bangunan & Ruang</h2>
        <span class="badge bg-secondary p-2 shadow-sm fs-7 rounded-3">
            <i class="bi bi-person-badge me-1"></i> Sesi Admin
        </span>
    </div>

    <!-- STATISTIK KARTU ATAS (Agregat Instan) -->
    <div class="row g-3 mb-4">
        <!-- Gedung -->
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm text-white bg-primary h-100 rounded-3">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <p class="mb-1 text-white-50 text-uppercase fw-semibold small">Total Gedung (Buildings)</p>
                        <h3 class="display-6 fw-bold mb-0"><?= $total_buildings; ?></h3>
                    </div>
                    <i class="bi bi-building fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
        <!-- Lantai -->
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm text-white bg-success h-100 rounded-3">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <p class="mb-1 text-white-50 text-uppercase fw-semibold small">Total Lantai (Floors)</p>
                        <h3 class="display-6 fw-bold mb-0"><?= $total_floors; ?></h3>
                    </div>
                    <i class="bi bi-layers fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
        <!-- Ruangan -->
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm text-white bg-danger h-100 rounded-3">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <p class="mb-1 text-white-50 text-uppercase fw-semibold small">Total Ruangan (Rooms)</p>
                        <h3 class="display-6 fw-bold mb-0"><?= $total_rooms; ?></h3>
                    </div>
                    <i class="bi bi-door-open fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW GRID DATA UTAMA -->
    <div class="row g-4">
        
        <!-- KOLOM 1: DAFTAR GEDUNG -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3 px-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-building me-2 text-primary"></i>Buildings</h5>
                    <button class="btn btn-primary btn-sm rounded-circle p-1 d-flex align-items-center justify-content-center" style="width:28px; height:28px;" onclick="bukaModalPaksa('modalAddBuilding')">
                        <i class="bi bi-plus fs-5"></i>
                    </button>
                </div>
                <div class="card-body px-3 pb-3 pt-2">
                    <div class="list-group list-group-flush border-top border-light">
                        <?php if (empty($buildings)): ?>
                            <div class="text-center text-muted py-4 small">Belum ada data gedung</div>
                        <?php else: ?>
                            <?php foreach ($buildings as $b): ?>
                                <div class="list-group-item px-0 py-3 border-bottom d-flex justify-content-between align-items-start">
                                    <div class="me-2 text-truncate">
                                        <div class="fw-bold text-dark text-truncate"><?= htmlspecialchars($b['nama']); ?></div>
                                        <small class="text-muted d-block text-truncate"><?= htmlspecialchars($b['alamat'] ?? '-'); ?></small>
                                    </div>
                                    <div class="d-flex gap-1 flex-shrink-0">
                                        <button class="btn btn-link btn-sm text-warning p-0" onclick="prosesEditBuilding(<?= $b['id']; ?>, '<?= addslashes($b['nama']); ?>', '<?= addslashes($b['alamat'] ?? ''); ?>')"><i class="bi bi-pencil-square fs-6"></i></button>
                                        <button class="btn btn-link btn-sm text-danger p-0" onclick="prosesHapusCrud('delete_building', <?= $b['id']; ?>, 'Hapus gedung ini?')"><i class="bi bi-trash3 fs-6"></i></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- KOLOM 2: DAFTAR LANTAI -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3 px-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-layers me-2 text-success"></i>Floors</h5>
                    <button class="btn btn-success btn-sm rounded-circle p-1 d-flex align-items-center justify-content-center" style="width:28px; height:28px;" onclick="bukaModalPaksa('modalAddFloor')">
                        <i class="bi bi-plus fs-5"></i>
                    </button>
                </div>
                <div class="card-body px-3 pb-3 pt-2">
                    <div class="list-group list-group-flush border-top border-light">
                        <?php if (empty($floors)): ?>
                            <div class="text-center text-muted py-4 small">Belum ada data lantai</div>
                        <?php else: ?>
                            <?php foreach ($floors as $f): ?>
                                <div class="list-group-item px-0 py-3 border-bottom d-flex justify-content-between align-items-start">
                                    <div class="me-2 text-truncate">
                                        <div class="fw-bold text-dark text-truncate"><?= htmlspecialchars($f['nama']); ?></div>
                                        <small class="text-muted d-block text-truncate"><i class="bi bi-building me-1"></i><?= htmlspecialchars($f['nama_bangunan'] ?? 'Tanpa Gedung'); ?></small>
                                    </div>
                                    <div class="d-flex gap-1 flex-shrink-0">
                                        <button class="btn btn-link btn-sm text-warning p-0" onclick="prosesEditFloor(<?= $f['id']; ?>, '<?= addslashes($f['nama']); ?>', <?= (int)$f['building_id']; ?>)"><i class="bi bi-pencil-square fs-6"></i></button>
                                        <button class="btn btn-link btn-sm text-danger p-0" onclick="prosesHapusCrud('delete_floor', <?= $f['id']; ?>, 'Hapus lantai ini?')"><i class="bi bi-trash3 fs-6"></i></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- KOLOM 3: MANAJEMEN ROOMS (RUANGAN) -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-door-open me-2 text-danger"></i> Rooms</h5>
                    <!-- Tombol Tambah Ruangan -->
                    <button type="button" class="btn btn-danger btn-sm rounded-circle px-2 py-1" onclick="bukaModalPaksa('modalAddRoom')">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                <div class="card-body p-3 overflow-auto" style="max-height: 500px;">
                    <?php if (empty($rooms)): ?>
                        <div class="text-center text-muted py-4">Belum ada data ruangan.</div>
                    <?php else: ?>
                        <?php foreach ($rooms as $r): ?>
                            <div class="p-3 mb-3 bg-light rounded border d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($r['nama']); ?></h6>
                                        <?php if (!empty($r['kode'])): ?>
                                            <span class="badge bg-dark rounded-pill" style="font-size: 0.75rem;"><?= htmlspecialchars($r['kode']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted d-block mb-1"><i class="bi bi-layers me-1"></i> <?= htmlspecialchars($r['nama_lantai'] ?? 'Tanpa Lantai'); ?></small>
                                    <?php if (!empty($r['telepon'])): ?>
                                        <small class="text-secondary d-block" style="font-size: 0.8rem;"><i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($r['telepon']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-warning border-0" onclick="prosesEditRoom(<?= $r['id']; ?>, '<?= addslashes($r['nama']); ?>', <?= (int)$r['floor_id']; ?>, '<?= addslashes($r['kode'] ?? ''); ?>', '<?= addslashes($r['telepon'] ?? ''); ?>')"><i class="bi bi-pencil-square"></i></button>
                                    <button class="btn btn-outline-danger border-0" onclick="prosesHapusCrud('delete_room', <?= $r['id']; ?>, 'Hapus ruangan ini?')"><i class="bi bi-trash3"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>        

<!-- ========================================== -->
<!-- MODAL POPUP INPUT DATA (TAMBAH DATA)       -->
<!-- ========================================== -->

<!-- Modal Add Building (Disesuaikan ID: modalAddBuilding) -->
<div class="modal fade" id="modalAddBuilding" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form onsubmit="prosesTambahCrud(event, 'add_building')">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-building me-2"></i> Tambah Gedung</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="tutupModalPaksa('modalAddBuilding')"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Gedung</label>
                        <input type="text" name="nama" class="form-control" required placeholder="Contoh: Gedung A">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Alamat Gedung</label>
                        <textarea name="alamat" class="form-control" rows="3" placeholder="Contoh: Jl. Ahmad Yani No. 12" required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" onclick="tutupModalPaksa('modalAddBuilding')">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Add Floor (Disesuaikan ID: modalAddFloor) -->
<div class="modal fade" id="modalAddFloor" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form onsubmit="prosesTambahCrud(event, 'add_floor')">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-layers me-2"></i> Tambah Lantai</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="tutupModalPaksa('modalAddFloor')"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Gedung</label>
                        <select name="building_id" class="form-select" required>
                            <option value="">-- Pilih Gedung --</option>
                            <?php foreach ($buildings as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Nama Lantai</label>
                        <input type="text" name="nama" class="form-control" required placeholder="Contoh: Lantai 1">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" onclick="tutupModalPaksa('modalAddFloor')">Batal</button>
                    <button type="submit" class="btn btn-success px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Add Room (Disesuaikan ID: modalAddRoom) -->
<div class="modal fade" id="modalAddRoom" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form onsubmit="prosesTambahCrud(event, 'add_room')">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-door-open me-2"></i> Tambah Ruangan</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="tutupModalPaksa('modalAddRoom')"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Lantai</label>
                        <select name="floor_id" class="form-select" required>
                            <option value="">-- Pilih Lantai --</option>
                            <!-- Menggunakan array hasil manipulasi $floors lokal agar relasi nama gedung terbaca sempurna -->
                            <?php foreach ($floors as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nama']) ?> (<?= htmlspecialchars($f['nama_bangunan'] ?? 'Tanpa Gedung') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Ruangan</label>
                        <input type="text" name="nama" class="form-control" required placeholder="Contoh: Ruang Rapat 401">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kode Ruangan</label>
                        <input type="text" name="kode" class="form-control" placeholder="Contoh: R01">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Telepon Ruangan</label>
                        <input type="text" name="telepon" class="form-control" placeholder="Contoh: 021-xxxx">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" onclick="tutupModalPaksa('modalAddRoom')">Batal</button>
                    <button type="submit" class="btn btn-danger px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL POPUP EDIT DATA                      -->
<!-- ========================================== -->

<!-- Modal Edit Building -->
<div class="modal fade" id="modalEditBuilding" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form onsubmit="simpanEditCrud(event, 'edit_building')">
                <input type="hidden" name="id" id="edit_b_id">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Data Gedung</h5>
                    <button type="button" class="btn-close" onclick="tutupModalPaksa('modalEditBuilding')"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Gedung</label>
                        <input type="text" name="nama" id="edit_b_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Gedung</label>
                        <textarea name="alamat" id="edit_b_alamat" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalPaksa('modalEditBuilding')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Floor -->
<div class="modal fade" id="modalEditFloor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form onsubmit="simpanEditCrud(event, 'edit_floor')">
                <input type="hidden" name="id" id="edit_f_id">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Lantai</h5>
                    <button type="button" class="btn-close" onclick="tutupModalPaksa('modalEditFloor')"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Gedung</label>
                        <select name="building_id" id="edit_f_building_id" class="form-select" required>
                            <?php foreach ($buildings as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lantai</label>
                        <input type="text" name="nama" id="edit_f_nama" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalPaksa('modalEditFloor')">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Room -->
<div class="modal fade" id="modalEditRoom" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form onsubmit="simpanEditCrud(event, 'edit_room')">
                <input type="hidden" name="id" id="edit_r_id">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Ruangan</h5>
                    <button type="button" class="btn-close" onclick="tutupModalPaksa('modalEditRoom')"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Lantai</label>
                        <select name="floor_id" id="edit_r_floor_id" class="form-select" required>
                            <?php foreach ($floors as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Ruangan</label>
                        <input type="text" name="nama" id="edit_r_nama" class="form-control" required>
                    </div>
                    <!-- INPUT BARU: KODE RUANGAN -->
                    <div class="mb-3">
                        <label class="form-label">Kode Ruangan</label>
                        <input type="text" name="kode" id="edit_r_kode" class="form-control" placeholder="Contoh: R01">
                    </div>
                    <!-- INPUT BARU: TELEPON RUANGAN -->
                    <div class="mb-3">
                        <label class="form-label">Telepon Ruangan</label>
                        <input type="text" name="telepon" id="edit_r_telepon" class="form-control" placeholder="Contoh: 021-xxxx">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalPaksa('modalEditRoom')">Batal</button>
                    <!-- Mengubah btn-danger menjadi btn-primary agar selaras dengan tombol simpan lainnya -->
                    <button type="submit" class="btn btn-primary">Simpan</button> 
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- LOGIKA JAVASCRIPT UTAMA AJAX CRUD          -->
<!-- ========================================== -->
<script>
// 1. Fungsi Membuka Modal secara Paksa (Anti Bentrok Library)
function bukaModalPaksa(idModal) {
    const modalTarget = document.getElementById(idModal);
    if (modalTarget) {
        modalTarget.classList.add('show');
        modalTarget.style.display = 'block';
        modalTarget.removeAttribute('aria-hidden');
        modalTarget.setAttribute('aria-modal', 'true');
        modalTarget.setAttribute('role', 'dialog');
        
        // Membuat efek backdrop hitam transparan di belakang modal
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

// Event otomatis mendeteksi klik tombol X (close) bawaan modal
document.addEventListener("click", function(e) {
    if (e.target.classList.contains('btn-close') || e.target.getAttribute('data-bs-dismiss') === 'modal') {
        const modalTerbuka = e.target.closest('.modal');
        if (modalTerbuka) {
            tutupModalPaksa(modalTerbuka.id);
        }
    }
});

// 3. Fungsi Tambah Data Berbagai Komponen (Create)
function prosesTambahCrud(event, aksi) {
    event.preventDefault(); 
    let targetForm = event.target;
    let payload = new FormData(targetForm);
    payload.append('action', aksi); 

    // Dialihkan ke proses_relasi.php agar tidak loop loading berat
    fetch('proses_relasi.php', {
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

// 4. Fungsi Hapus Data Berbagai Komponen (Delete)
function prosesHapusCrud(aksi, idTarget, teksKonfirmasi) {
    if (!confirm(teksKonfirmasi)) return;
    let payload = new FormData();
    payload.append('action', aksi);
    payload.append('id', idTarget);

    // Dialihkan ke proses_relasi.php
    fetch('proses_relasi.php', {
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

// 5. Fungsi Pemicu Pengisian Data Lama ke Input Form Modal Edit
function prosesEditBuilding(id, namaLama, alamatLama) {
    document.getElementById('edit_b_id').value = id;
    document.getElementById('edit_b_nama').value = namaLama;
    document.getElementById('edit_b_alamat').value = alamatLama; 
    bukaModalPaksa('modalEditBuilding');
}

function prosesEditFloor(id, namaLama, buildingIdLama) {
    document.getElementById('edit_f_id').value = id;
    document.getElementById('edit_f_nama').value = namaLama;
    document.getElementById('edit_f_building_id').value = buildingIdLama;
    bukaModalPaksa('modalEditFloor');
}

function prosesEditRoom(id, namaLama, floorIdLama, kodeLama, teleponLama) {
    document.getElementById('edit_r_id').value = id;
    document.getElementById('edit_r_nama').value = namaLama;
    document.getElementById('edit_r_floor_id').value = floorIdLama;
    document.getElementById('edit_r_kode').value = kodeLama;
    document.getElementById('edit_r_telepon').value = teleponLama;
    bukaModalPaksa('modalEditRoom');
}

// 6. Fungsi Eksekusi Pengiriman Data Modifikasi Baru ke Server (Update)
function simpanEditCrud(event, aksi) {
    event.preventDefault();
    let payload = new FormData(event.target);
    payload.append('action', aksi);

    // Dialihkan ke proses_relasi.php
    fetch('proses_relasi.php', {
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
