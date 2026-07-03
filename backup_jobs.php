<?php
require_once __DIR__ . '/auth.php';
require_login();

// =========================================================================
// 1. KONFIGURASI DATABASE & PROSES CRUD
// =========================================================================
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

// Paginasi
$perPage = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- PROSES TAMBAH JOB BACKUP (CREATE) ---
    if (isset($_POST['action']) && $_POST['action'] == 'create') {
        $job_name = trim($_POST['job_name']);
        $backup_type = $_POST['backup_type']; // 'database' atau 'files'
        $schedule = $_POST['schedule'];       // 'daily', 'weekly', etc.
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

        $sql = "INSERT INTO backup_jobs (job_name, backup_type, schedule, status) 
                VALUES (:job_name, :backup_type, :schedule, :status)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':job_name', $job_name);
        $stmt->bindParam(':backup_type', $backup_type);
        $stmt->bindParam(':schedule', $schedule);
        $stmt->bindParam(':status', $status, PDO::PARAM_INT);

        if ($stmt->execute()) {
            header("Location: backup_jobs.php?success=create");
            exit;
        }
    }

    // --- PROSES MENJALANKAN MANUAL BACKUP ON-THE-FLY (Pemicu/Trigger) ---
    if (isset($_POST['action']) && $_POST['action'] == 'run_backup') {
        $job_id = (int)$_POST['id'];
        
        // Contoh logika trigger dump database otomatis (menggunakan mysqldump)
        $filename = "backup_" . database . "_" . time() . ".sql";
        $targetPath = __DIR__ . "/uploads/backups/" . $filename;
        
        // Perintah CLI shell Linux untuk backup database
        $command = "mysqldump -h $host -u $username -p$password $database > $targetPath";
        system($command, $outputCode);

        if ($outputCode === 0) {
            // Update waktu backup terakhir jika berhasil
            $updateLog = $conn->prepare("UPDATE backup_jobs SET last_run = NOW(), last_status = 'success' WHERE id = :id");
            $updateLog->bindParam(':id', $job_id);
            $updateLog->execute();
            header("Location: backup_jobs.php?success=backup_run");
        } else {
            $updateLog = $conn->prepare("UPDATE backup_jobs SET last_status = 'failed' WHERE id = :id");
            $updateLog->bindParam(':id', $job_id);
            $updateLog->execute();
            header("Location: backup_jobs.php?error=backup_failed");
        }
        exit;
    }

    // --- PROSES HAPUS JOB (DELETE) ---
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM backup_jobs WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            header("Location: backup_jobs.php?success=delete");
            exit;
        }
    }

    // --- PROSES MENAMPILKAN DATA (READ) ---
    $totalQuery = $conn->query("SELECT COUNT(*) FROM backup_jobs");
    $totalRows = $totalQuery->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);

    $stmt = $conn->prepare("SELECT * FROM backup_jobs ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $backupJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
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
      
      <li class="nav-item">
        <a href="dashboard.php" class="nav-link <?= checkActiveMenu('dashboard.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-house-door me-3"></i> Dashboard</a>
      </li>
      
      <li class="nav-item">
        <a href="roles.php" class="nav-link <?= checkActiveMenu('roles.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-shield-lock me-3"></i> Manajemen Roles</a>
      </li>
      
      <li class="nav-item">
        <a href="relasi.php" class="nav-link <?= checkActiveMenu('relasi.php', $currentFile) ?> rounded-end d-flex align-items-center text-truncate" title="Manajemen Bangunan & Ruang">
          <i class="bi bi-diagram-3 me-3 flex-shrink-0"></i> <span class="text-truncate">Manajemen Bangunan & Ruang</span>
        </a>
      </li>
      
      <li class="nav-item">
        <a href="assets.php" class="nav-link <?= checkActiveMenu('assets.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-folder2-open me-3"></i> Assets</a>
      </li>
      
      <li class="nav-item">
        <a href="manajemen_asset.php" class="nav-link <?= checkActiveMenu('manajemen_asset.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-boxes me-3"></i> Manajemen Asset</a>
      </li>
      
      <li class="nav-item">
        <a href="asset_movements.php" class="nav-link <?= checkActiveMenu('asset_movements.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-arrow-left-right me-3"></i> Log Perpindahan</a>
      </li>
      
      <li class="nav-item">
        <a href="server.php" class="nav-link <?= checkActiveMenu('server.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-hdd-network me-3"></i> Server</a>
      </li>
      
      <li class="nav-item">
        <a href="network_device.php" class="nav-link <?= checkActiveMenu('network_device.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-router me-3"></i> Network Device</a>
      </li>
      
      <li class="nav-item">
        <a href="network_port.php" class="nav-link <?= checkActiveMenu('network_port.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-ethernet me-3"></i> Network Port</a>
      </li>
      
      <li class="nav-item">
        <a href="vendors.php" class="nav-link <?= checkActiveMenu('vendors.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-building me-3"></i> Vendors</a>
      </li>
      
      <li class="nav-item">
        <a href="password_categories.php" class="nav-link <?= checkActiveMenu('password_categories.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-grid-fill me-3"></i> Password Categories</a>
      </li>
      
      <li class="nav-item">
        <a href="password_vault.php" class="nav-link <?= checkActiveMenu('password_vault.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-safe me-3"></i> Password Vault</a>
      </li>
      
      <li class="nav-item">
        <a href="tickets.php" class="nav-link <?= checkActiveMenu('tickets.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-ticket-perforated-fill me-3"></i> Tikets</a>
      </li>
      
      <li class="nav-item">
        <a href="maintenance.php" class="nav-link <?= checkActiveMenu('maintenance.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-wrench-adjustable-circle me-3"></i> Maintenance</a>
      </li>
      
      <li class="nav-item"> 
        <a href="knowledge_categories.php" class="nav-link <?= checkActiveMenu('knowledge_categories.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-tags-fill me-3"></i> Knowledge Categories</a> 
      </li> 
      
      <li class="nav-item"> 
        <a href="knowledge_articles.php" class="nav-link <?= checkActiveMenu('knowledge_articles.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-file-earmark-text-fill me-3"></i> Knowledge Articles</a> 
      </li> 
      
      <li class="nav-item"> 
        <a href="sops.php" class="nav-link <?= checkActiveMenu('sops.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-journal-text me-3"></i> SOPS</a> 
      </li> 
      
      <li class="nav-item"> 
        <a href="sop_categories.php" class="nav-link <?= checkActiveMenu('sop_categories.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-tags me-3"></i> SOP Categories</a> 
      </li> 
      
      <li class="nav-item"> 
        <a href="software_licenses.php" class="nav-link <?= checkActiveMenu('software_licenses.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-key-fill me-3"></i> Software Licenses</a> 
      </li> 
      <li class="nav-item"> 
        <a href="backup_jobs.php" class="nav-link <?= checkActiveMenu('backup_jobs.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-database-fill-gear me-3"></i> Backup Jobs</a> 
      </li>
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

<!-- ==================================================================== -->
<!-- BAGIAN C: PEMBUNGKUS MAIN KONTEN UTAMA (SEKARANG SUDAH DI LUAR)       -->
<!-- ==================================================================== -->
<main class="main-content p-3 p-md-4 flex-grow-1 overflow-x-hidden">
    