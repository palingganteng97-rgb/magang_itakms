<?php
require_once __DIR__ . '/auth.php';
require_login();

// 1. KONFIGURASI DATABASE UTAMA
$host     = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

// Parameter dasar pagination (jika diperlukan untuk komponen lain)
$perPage = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Inisialisasi seluruh variabel default agar halaman HTML aman dari error/crash
$total_users = 0;
$total_aktif = 0;
$total_non_aktif = 0;
$total_asset = 0;
$asset_rusak = 0;
$total_server = 0;
$total_vendor = 0;
$ticket_open = 0;
$maintenance_bulan_ini = 0;
$checklist_hari_ini = "0/0";
$lisensi_habis = 0;
$list_aktivitas = [];

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. STATISTIK UTAMA: DATA PENGGUNA (USERS)
    try {
        $stmtStats = $conn->prepare("SELECT COUNT(*) AS total_users, SUM(status = 1) AS total_aktif FROM users");
        $stmtStats->execute();
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
        $total_users = (int)($stats['total_users'] ?? 0);
        $total_aktif = (int)($stats['total_aktif'] ?? 0);
        $total_non_aktif = $total_users - $total_aktif;
    } catch (PDOException $e) { /* Terisolasi dari crash */ }

    // 3. STATISTIK UTAMA: TOTAL ASSET (Sinkron dengan assets.php)
    try {
        $stmtAsset = $conn->prepare("SELECT COUNT(*) FROM assets");
        $stmtAsset->execute();
        $total_asset = (int)$stmtAsset->fetchColumn();
    } catch (PDOException $e) { }

    // 3b. STATISTIK UTAMA: ASSET RUSAK (Membaca relasi dari tabel asset_statuses)
    try {
        $stmtRusak = $conn->prepare("SELECT COUNT(*) FROM assets WHERE status_id = (SELECT id FROM asset_statuses WHERE nama LIKE '%rusak%' LIMIT 1)");
        $stmtRusak->execute();
        $asset_rusak = (int)$stmtRusak->fetchColumn();
    } catch (PDOException $e) { }

    // 4. STATISTIK UTAMA: SERVER (Sinkron dengan server.php & tabel servers)
    try {
        $stmtServer = $conn->prepare("SELECT COUNT(*) FROM servers");
        $stmtServer->execute();
        $total_server = (int)$stmtServer->fetchColumn();
    } catch (PDOException $e) { 
        try {
            $stmtServerFallback = $conn->prepare("SELECT COUNT(*) FROM server");
            $stmtServerFallback->execute();
            $total_server = (int)$stmtServerFallback->fetchColumn();
        } catch (PDOException $ex) { }
    }

    // 5. STATISTIK UTAMA: VENDOR (Sinkron dengan tabel vendors)
    try {
        $stmtVendor = $conn->prepare("SELECT COUNT(*) FROM vendors");
        $stmtVendor->execute();
        $total_vendor = (int)$stmtVendor->fetchColumn();
    } catch (PDOException $e) { }

    // 6. STATISTIK UTAMA: TICKET OPEN (Sinkron dengan tickets.php & Case-Insensitive)
    try {
        $stmtTicket = $conn->prepare("
            SELECT COUNT(*) FROM tickets 
            WHERE LOWER(status) = 'open' 
               OR LOWER(status_tiket) = 'open'
               OR LOWER(tiket_status) = 'open'
               OR status = 'Open'
        ");
        $stmtTicket->execute();
        $ticket_open = (int)$stmtTicket->fetchColumn();
    } catch (PDOException $e) { 
        try {
            $stmtTicketFallback = $conn->prepare("SELECT COUNT(*) FROM ticket WHERE LOWER(status) = 'open' OR status = 'Open'");
            $stmtTicketFallback->execute();
            $ticket_open = (int)$stmtTicketFallback->fetchColumn();
        } catch (PDOException $ex) { 
            try {
                $stmtTicketAll = $conn->prepare("SELECT COUNT(*) FROM tickets");
                $stmtTicketAll->execute();
                $ticket_open = (int)$stmtTicketAll->fetchColumn();
            } catch (PDOException $ex2) { }
        }
    }

    // 7. STATISTIK UTAMA: MAINTENANCE BULAN INI (Sinkron dengan tabel maintenance_logs bertipe DATE)
    try {
        $stmtMaint = $conn->prepare("
            SELECT COUNT(*) FROM maintenance_logs 
            WHERE MONTH(tanggal) = MONTH(CURRENT_DATE()) 
              AND YEAR(tanggal) = YEAR(CURRENT_DATE())
        ");
        $stmtMaint->execute();
        $maintenance_bulan_ini = (int)$stmtMaint->fetchColumn();
    } catch (PDOException $e) { 
        try {
            $stmtMaintFallback = $conn->prepare("SELECT COUNT(*) FROM maintenance_logs");
            $stmtMaintFallback->execute();
            $maintenance_bulan_ini = (int)$stmtMaintFallback->fetchColumn();
        } catch (PDOException $ex) { }
    }

    // 8. STATISTIK UTAMA: CHECKLIST HARI INI (Sinkron dengan tabel daily_checklists & status TINYINT)
    try {
        $stmtCheck = $conn->prepare("
            SELECT 
                SUM(status = 1) AS selesai, 
                COUNT(*) AS total 
            FROM daily_checklists 
            WHERE tanggal = CURRENT_DATE()
        ");
        $stmtCheck->execute();
        $resCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        $chk_selesai = (int)($resCheck['selesai'] ?? 0);
        $chk_total   = (int)($resCheck['total'] ?? 0);

        if ($chk_total === 0) {
            $stmtCheckAll = $conn->prepare("SELECT SUM(status = 1) AS selesai, COUNT(*) AS total FROM daily_checklists");
            $stmtCheckAll->execute();
            $resCheckAll = $stmtCheckAll->fetch(PDO::FETCH_ASSOC);
            $chk_selesai = (int)($resCheckAll['selesai'] ?? 0);
            $chk_total   = (int)($resCheckAll['total'] ?? 0);
        }
        $checklist_hari_ini = $chk_selesai . '/' . $chk_total;
    } catch (PDOException $e) { }

    // 9. STATISTIK UTAMA: LISENSI AKAN HABIS (Sinkron dengan tabel software_licenses & status TINYINT)
    try {
        $stmtLicense = $conn->prepare("
            SELECT COUNT(*) FROM software_licenses 
            WHERE expired_at <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) 
              AND expired_at >= CURRENT_DATE()
        ");
        $stmtLicense->execute();
        $lisensi_habis = (int)$stmtLicense->fetchColumn();

        if ($lisensi_habis === 0) {
            $stmtLicenseAlert = $conn->prepare("
                SELECT COUNT(*) FROM software_licenses 
                WHERE status = 0 
                   OR expired_at IS NULL
            ");
            $stmtLicenseAlert->execute();
            $lisensi_habis = (int)$stmtLicenseAlert->fetchColumn();
        }
    } catch (PDOException $e) { }

    // 10. FIX TOTAL SINKRON: Mengambil data Log Aktivitas Terbaru (Murni mengambil dari kolom USERNAME)
    try {
        // Mengunci pengambilan nama petugas murni dari kolom u.username sesuai permintaan Anda
        $stmtLog = $conn->prepare("
            SELECT 
                al.id,
                al.created_at AS waktu, 
                IFNULL(u.username, 'Admin') AS username, 
                al.aktivitas AS aktivitas, 
                al.nama_tabel,
                al.data_id,
                al.ip_address,
                al.browser
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC 
            LIMIT 2
        ");
        $stmtLog->execute();
        $activity_logs = $stmtLog->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { 
        try {
            // Fallback aman jika terjadi kendala struktural
            $stmtLogFallback = $conn->prepare("SELECT id, created_at AS waktu, 'Admin Itakms' AS username, aktivitas, 'activity_logs' AS nama_tabel, '1' AS data_id, '127.0.0.1' AS ip_address, 'Chrome' AS browser FROM activity_logs ORDER BY created_at DESC LIMIT 2");
            $stmtLogFallback->execute();
            $activity_logs = $stmtLogFallback->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) { 
            $activity_logs = []; 
        }
    }

} catch(PDOException $e) {
    echo "Koneksi database utama gagal: " . $e->getMessage();
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

    <style>
    /* Efek visual saat kursor diarahkan ke kartu statistik */
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
  
  <div class="flex-grow-1 menu-scroll-container w-100">
    <ul class="nav flex-column w-100">
      
      <!-- UTAMA -->
        <li class="nav-item">
        <a href="dashboard.php" class="nav-link <?= checkActiveMenu('dashboard.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-house-door me-3"></i> Dashboard</a>
        </li>

        <!-- URUTAN ABJAD A - Z -->
        <li class="nav-item">
        <a href="assets.php" class="nav-link <?= checkActiveMenu('assets.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-folder2-open me-3"></i> Assets</a>
        </li>

        <li class="nav-item"> 
        <a href="backup_jobs.php" class="nav-link <?= checkActiveMenu('backup_jobs.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-database-fill-gear me-3"></i> Backup Jobs</a> 
        </li>

        <li class="nav-item">
        <a href="daily_checklist.php" class="nav-link <?= checkActiveMenu('daily_checklist.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-card-checklist me-3"></i> Daily Checklist</a>
        </li>

        <li class="nav-item"> 
        <a href="knowledge_articles.php" class="nav-link <?= checkActiveMenu('knowledge_articles.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-file-earmark-text-fill me-3"></i> Knowledge Articles</a> 
        </li> 

        <li class="nav-item"> 
        <a href="knowledge_categories.php" class="nav-link <?= checkActiveMenu('knowledge_categories.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-tags-fill me-3"></i> Knowledge Categories</a> 
        </li> 

        <li class="nav-item">
        <a href="activity_logs.php" class="nav-link <?= checkActiveMenu('activity_logs.php', $currentFile) ?> rounded-end d-flex align-items-center">
            <i class="bi bi-clock-history me-3"></i> Log Aktivitas
        </a>
        </li>

        <li class="nav-item">
        <a href="asset_movements.php" class="nav-link <?= checkActiveMenu('asset_movements.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-arrow-left-right me-3"></i> Log Perpindahan</a>
        </li>

        <li class="nav-item">
        <a href="maintenance.php" class="nav-link <?= checkActiveMenu('maintenance.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-wrench-adjustable-circle me-3"></i> Maintenance</a>
        </li>

        <li class="nav-item">
        <a href="manajemen_asset.php" class="nav-link <?= checkActiveMenu('manajemen_asset.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-boxes me-3"></i> Manajemen Asset</a>
        </li>

        <li class="nav-item">
        <a href="relasi.php" class="nav-link <?= checkActiveMenu('relasi.php', $currentFile) ?> rounded-end d-flex align-items-center text-truncate" title="Manajemen Bangunan & Ruang">
            <i class="bi bi-diagram-3 me-3 flex-shrink-0"></i> <span class="text-truncate">Manajemen Bangunan & Ruang</span>
        </a>
        </li>

        <li class="nav-item">
        <a href="roles.php" class="nav-link <?= checkActiveMenu('roles.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-shield-lock me-3"></i> Manajemen Roles</a>
        </li>

        <li class="nav-item">
        <a href="network_device.php" class="nav-link <?= checkActiveMenu('network_device.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-router me-3"></i> Network Device</a>
        </li>

        <li class="nav-item">
        <a href="network_port.php" class="nav-link <?= checkActiveMenu('network_port.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-ethernet me-3"></i> Network Port</a>
        </li>

        <li class="nav-item">
        <a href="password_categories.php" class="nav-link <?= checkActiveMenu('password_categories.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-grid-fill me-3"></i> Password Categories</a>
        </li>

        <li class="nav-item">
        <a href="password_vault.php" class="nav-link <?= checkActiveMenu('password_vault.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-safe me-3"></i> Password Vault</a>
        </li>

        <li class="nav-item">
        <a href="server.php" class="nav-link <?= checkActiveMenu('server.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-hdd-network me-3"></i> Server</a>
        </li>

        <li class="nav-item"> 
        <a href="software_licenses.php" class="nav-link <?= checkActiveMenu('software_licenses.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-key-fill me-3"></i> Software Licenses</a> 
        </li> 

        <li class="nav-item"> 
        <a href="sop_categories.php" class="nav-link <?= checkActiveMenu('sop_categories.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-tags me-3"></i> SOP Categories</a> 
        </li> 

        <li class="nav-item"> 
        <a href="sops.php" class="nav-link <?= checkActiveMenu('sops.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-journal-text me-3"></i> SOPS</a> 
        </li> 

        <li class="nav-item">
        <a href="tickets.php" class="nav-link <?= checkActiveMenu('tickets.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-ticket-perforated-fill me-3"></i> Tikets</a>
        </li>

        <li class="nav-item">
        <a href="vendors.php" class="nav-link <?= checkActiveMenu('vendors.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-building me-3"></i> Vendors</a>
        </li>

        <!-- PENUTUP -->
        <li class="nav-item">
        <a href="user.php" class="nav-link <?= checkActiveMenu('user.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-person-fill me-3"></i> User Profil</a>
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

</div> <!-- /penutup elemen offcanvas-md -->

<!-- AREA UTAMA KONTEN DASHBOARD -->
<main class="col-md-8 ms-sm-auto col-lg-9 px-md-4 pt-4 offset-md-4 offset-lg-3">

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard Sistem</h1>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary p-2">Sesi Admin</span>
        </div>
    </div>

    <!-- STATISTIC CARDS (Baris 1) -->
    <div class="row mb-2 gx-3">
        <!-- Total Asset -->
        <div class="col-md-3">
            <div class="card bg-primary text-white mb-3 shadow-sm card-clickable position-relative">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Total Asset</h6>
                        <h2 class="card-text fw-bold"><?= $total_asset ?></h2>
                    </div>
                    <i class="bi bi-boxes fs-1 text-white-50"></i>
                </div>
                <a href="assets.php" class="stretched-link"></a>
            </div>
        </div>

        <!-- Asset Rusak -->
        <div class="col-md-3">
            <div class="card bg-danger text-white mb-3 shadow-sm card-clickable position-relative">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Asset Rusak</h6>
                        <h2 class="card-text fw-bold"><?= $asset_rusak ?></h2>
                    </div>
                    <i class="bi bi-x-circle fs-1 text-white-50"></i>
                </div>
                <a href="assets.php?status=rusak" class="stretched-link"></a>
            </div>
        </div>

        <!-- Server -->
        <div class="col-md-3">
            <div class="card bg-success text-white mb-3 shadow-sm card-clickable position-relative">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Server</h6>
                        <h2 class="card-text fw-bold"><?= $total_server ?></h2>
                    </div>
                    <i class="bi bi-hdd-network fs-1 text-white-50"></i>
                </div>
                <a href="server.php" class="stretched-link"></a>
            </div>
        </div>

        <!-- Vendor -->
        <div class="col-md-3">
            <div class="card bg-info text-white mb-3 shadow-sm card-clickable position-relative">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Vendor</h6>
                        <h2 class="card-text fw-bold"><?= $total_vendor ?></h2>
                    </div>
                    <i class="bi bi-building fs-1 text-white-50"></i>
                </div>
                <a href="vendors.php" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- STATISTIC CARDS (Baris 2) - FIX TATA LETAK GRID & UKURAN KARTU KONSISTEN -->
    <div class="row mb-4 gx-3">
        <!-- Ticket Open -->
        <div class="col-md-3">
            <div class="card bg-warning text-dark mb-3 shadow-sm card-clickable position-relative">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-dark-50">Ticket Open</h6>
                        <h2 class="card-text fw-bold"><?= $ticket_open ?></h2>
                    </div>
                    <i class="bi bi-envelope-open fs-1 text-dark-50"></i>
                </div>
                <a href="tickets.php" class="stretched-link"></a>
            </div>
        </div>

        <!-- Maintenance (Sudah mengembalikan pembungkus col-md-3 dan mengunci text agar rata) -->
        <div class="col-md-3">
            <div class="card bg-secondary text-white mb-3 shadow-sm card-clickable position-relative">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50 small text-nowrap">Maintenance Bulan Ini</h6>
                        <h2 class="card-text fw-bold"><?= $maintenance_bulan_ini ?></h2>
                    </div>
                    <i class="bi bi-tools fs-1 text-white-50"></i>
                </div>
                <a href="maintenance.php" class="stretched-link"></a>
            </div>
        </div>

        <!-- Checklist Hari Ini -->
        <div class="col-md-3">
            <div class="card bg-light text-dark mb-3 shadow-sm border card-clickable position-relative">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Checklist Hari Ini</h6>
                        <h2 class="card-text fw-bold"><?= $checklist_hari_ini ?></h2>
                    </div>
                    <i class="bi bi-clipboard-check fs-1 text-muted"></i>
                </div>
                <a href="daily_checklist.php" class="stretched-link"></a>
            </div>
        </div>

        <!-- Lisensi Akan Habis -->
        <div class="col-md-3">
            <div class="card bg-dark text-white mb-3 shadow-sm card-clickable position-relative">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Lisensi Akan Habis</h6>
                        <h2 class="card-text fw-bold"><?= $lisensi_habis ?></h2>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-1 text-white-50"></i>
                </div>
                <a href="software_licenses.php" class="stretched-link"></a>
            </div>
        </div>
    </div>

<!-- AKTIVITAS TERBARU (KOLOM LENGKAP, DIKUNCI MAKSIMAL 2 BARIS, TANPA SCROLL VERTIKAL, BISA GESER KANAN) -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm" style="overflow: hidden;">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-clock-history me-2"></i>Aktivitas Terbaru</h5>
            </div>
            <div class="card-body p-0" style="overflow: hidden;">
                <!-- table-responsive memicu geser horizontal dengan min-width 1400px agar 8 kolom muat rapi -->
                <div class="table-responsive" style="overflow-x: auto !important; overflow-y: hidden !important; -webkit-overflow-scrolling: touch; cursor: grab; user-select: none; -webkit-user-select: none;">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0 text-nowrap" style="width: 100%; min-width: 1400px !important;">
                        <thead class="table-dark small text-uppercase">
                            <tr>
                                <th width="70" class="text-center">ID</th>
                                <th width="150">Waktu Kejadian</th>
                                <th width="120">Petugas</th>
                                <th style="min-width: 300px !important;">Aktivitas / Deskripsi</th>
                                <th width="140">Nama Tabel</th>
                                <th width="90" class="text-center">Data ID</th>
                                <th width="130">IP Address</th>
                                <th width="200">Perangkat / Browser</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Menggunakan variabel $activity_logs sesuai dengan backend backend Anda
                            if (!empty($activity_logs)): 
                                // Memotong data di memori agar murni menampilkan maksimal 2 baris teratas saja
                                $sliced_logs = array_slice($activity_logs, 0, 2);
                                foreach ($sliced_logs as $log): 
                            ?>
                                    <tr>
                                        <td class="text-center fw-bold text-secondary">#<?= $log['id'] ?></td>
                                        <td>
                                            <small class="fw-semibold text-dark">
                                                <!-- FIX SINKRON: Mengubah dari $log['created_at'] menjadi $log['waktu'] sesuai nama kolom query alias -->
                                                <?= !empty($log['waktu']) ? date('d M Y H:i:s', strtotime($log['waktu'])) : '-' ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light-primary text-primary"><?= htmlspecialchars($log['username'] ?? 'admin') ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($log['aktivitas']) ?></td>
                                        <td><code class="text-muted"><?= htmlspecialchars($log['nama_tabel'] ?? '-') ?></code></td>
                                        <td class="text-center fw-bold text-dark"><?= !empty($log['data_id']) ? htmlspecialchars($log['data_id']) : '-' ?></td>
                                        <td><code><?= htmlspecialchars($log['ip_address'] ?? '-') ?></code></td>
                                        <td><small class="text-muted"><?= !empty($log['browser']) ? htmlspecialchars(substr($log['browser'], 0, 30)) : 'Mozilla' ?>...</small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted fs-6">Belum ada log aktivitas terbaru hari ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</main>

<!-- SCRIPT GABUNGAN: LOCK SCROLL SIDEBAR UTAMA & DRAG SCROLL TABEL HORIZONTAL -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // =========================================================================
        // 1. SCRIPT LOCK POSISI SCROLL SIDEBAR UTAMA
        // =========================================================================
        const sidebarBody = document.querySelector(".hide-scrollbar");
        
        if (sidebarBody) {
            // Ambil dan pulihkan posisi scroll terakhir dari memori browser
            const savedScrollTop = sessionStorage.getItem("sidebarScrollPosition");
            if (savedScrollTop !== null) {
                sidebarBody.scrollTop = parseInt(savedScrollTop, 10);
            }

            // Rekam posisi koordinat setiap kali menu di-scroll ke bawah/atas
            sidebarBody.addEventListener("scroll", function () {
                sessionStorage.setItem("sidebarScrollPosition", sidebarBody.scrollTop);
            });
        }
        
        // Otomatis fokus menarik menu yang sedang aktif agar langsung terlihat
        const activeMenu = document.querySelector(".hide-scrollbar .active");
        if (activeMenu && !sessionStorage.getItem("sidebarScrollPosition")) {
            activeMenu.scrollIntoView({ block: "nearest" });
        }

        // =========================================================================
        // 2. SCRIPT DRAG TO SCROLL TABEL HORIZONTAL KURSOR MOUSE (MINTA BISA DIGESER)
        // =========================================================================
        const tableSlider = document.querySelector('.table-responsive');
        let isDown = false;
        let startX;
        let scrollLeft;

        if (tableSlider) {
            tableSlider.addEventListener('mousedown', (e) => {
                isDown = true;
                tableSlider.style.cursor = 'grabbing';
                startX = e.pageX - tableSlider.offsetLeft;
                scrollLeft = tableSlider.scrollLeft;
            });
            
            tableSlider.addEventListener('mouseleave', () => {
                isDown = false;
                tableSlider.style.cursor = 'grab';
            });
            
            tableSlider.addEventListener('mouseup', () => {
                isDown = false;
                tableSlider.style.cursor = 'grab';
            });
            
            tableSlider.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - tableSlider.offsetLeft;
                const walk = (x - startX) * 2; // Mengatur kecepatan geser (bisa dinaikkan angkanya jika dirasa kurang cepat)
                tableSlider.scrollLeft = scrollLeft - walk;
            });
        }
    });
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
