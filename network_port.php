<?php
require_once __DIR__ . '/auth.php';
require_login();

// =========================================================================
// 1. BARIS WAJIB: DEKLARASI CURRENT PAGE UNTUK SIDEBAR DINAMIS
// =========================================================================
$currentPage = basename($_SERVER['PHP_SELF']);

// 2. Konfigurasi Koneksi Database
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

// Konfigurasi Pagination Tabel
$perPage = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Inisialisasi variabel notifikasi alert
$message = '';
$messageType = 'success';

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success_add') {
        $message = "Data Network Port baru berhasil ditambahkan!";
        $messageType = "success";
    } elseif ($_GET['status'] == 'success_update') {
        $message = "Perubahan data Network Port berhasil disimpan!";
        $messageType = "success";
    } elseif ($_GET['status'] == 'success_delete') {
        $message = "Data Network Port berhasil dihapus secara permanen!";
        $messageType = "danger";
    }
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // TRIGGER LOG OTOMATIS GLOBAL: Mencatat log kunjungan halaman
    if (!isset($_GET['page']) && !isset($_POST['action'])) {
        write_log($conn, "Membuka halaman Dashboard Sistem - Network Ports", "network_ports", null);
    }

    // ===================================================
    // LOGIKA PROSES AKSI FORM CRUD (POST & GET)
    // ===================================================

    // A. PROSES TAMBAH DATA PORT (Create)
    if (isset($_POST['action']) && $_POST['action'] == 'create') {
        $network_device_id = $_POST['network_device_id'];
        $port              = $_POST['port'];
        $nama              = $_POST['nama'];
        $status            = $_POST['status']; 

        $stmtInsert = $conn->prepare("INSERT INTO network_ports (network_device_id, port, nama, status) VALUES (?, ?, ?, ?)");
        $sukses_add = $stmtInsert->execute([$network_device_id, $port, $nama, $status]);
        
        // AMBIL ID BARU DAN TULIS LOG AKTIVITAS (CREATE)
        if ($sukses_add) {
            $new_port_id = $conn->lastInsertId();
            write_log($conn, "Menambahkan port jaringan baru: " . $port . " (" . $nama . ")", "network_ports", $new_port_id);
        }

        header("Location: network_port.php?status=success_add");
        exit;
    }

    // B. PROSES UBAH DATA PORT (Update)
    if (isset($_POST['action']) && $_POST['action'] == 'update') {
        $id                = $_POST['id'];
        $network_device_id = $_POST['network_device_id'];
        $port              = $_POST['port'];
        $nama              = $_POST['nama'];
        $status            = $_POST['status']; 

        $stmtUpdate = $conn->prepare("UPDATE network_ports SET network_device_id = ?, port = ?, nama = ?, status = ? WHERE id = ?");
        $sukses_edit = $stmtUpdate->execute([$network_device_id, $port, $nama, $status, $id]);
        
        // TULIS LOG AKTIVITAS (UPDATE)
        if ($sukses_edit) {
            write_log($conn, "Mengubah data port jaringan ID: " . $id . " (Port: " . $port . ")", "network_ports", $id);
        }

        header("Location: network_port.php?status=success_update");
        exit;
    }

    // C. PROSES HAPUS DATA PORT (Delete POST via Secure Modal)
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $idDelete = $_POST['id'];

        // 1. Ambil nomor port terlebih dahulu sebelum dihapus permanen untuk kebutuhan teks riwayat log
        $get_info = $conn->prepare("SELECT port, nama FROM network_ports WHERE id = ?");
        $get_info->execute([$idDelete]);
        $port_data = $get_info->fetch(PDO::FETCH_ASSOC);
        $port_log = $port_data ? $port_data['port'] . " (" . $port_data['nama'] . ")" : 'Unknown';

        // 2. Jalankan query hapus data
        $stmtDelete = $conn->prepare("DELETE FROM network_ports WHERE id = ?");
        $sukses_delete = $stmtDelete->execute([$idDelete]);
        
        // TULIS LOG AKTIVITAS (DELETE)
        if ($sukses_delete) {
            write_log($conn, "Menghapus data port jaringan: " . $port_log, "network_ports", $idDelete);
        }

        header("Location: network_port.php?status=success_delete");
        exit;
    }

    // ===================================================
    // LOGIKA PENGAMBILAN DATA UNTUK DITAMPILKAN (Read)
    // ===================================================

    // 1. Mengambil data list ID Perangkat & VLAN dari network_devices untuk Dropdown Modal
    $stmtDevices = $conn->prepare("SELECT id, vlan FROM network_devices ORDER BY id DESC");
    $stmtDevices->execute();
    $listDevices = $stmtDevices->fetchAll(PDO::FETCH_ASSOC);

    // 2. Menghitung total data network_ports untuk keperluan batasan Pagination
    $stmtCount = $conn->prepare("SELECT COUNT(*) FROM network_ports");
    $stmtCount->execute();
    $totalRows = $stmtCount->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);

    // 3. Mengambil record data dari tabel network_ports + INNER JOIN ke network_devices
    $stmtPorts = $conn->prepare("
        SELECT np.*, nd.vlan 
        FROM network_ports np
        LEFT JOIN network_devices nd ON np.network_device_id = nd.id
        ORDER BY np.id DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmtPorts->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmtPorts->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtPorts->execute();
    $ports = $stmtPorts->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    echo "Koneksi database atau query bermasalah: " . $e->getMessage();
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

<!-- ==================================================================== -->
<!-- BAGIAN C: PEMBUNGKUS MAIN KONTEN UTAMA (NETWORK PORT RESPONSIVE)    -->
<!-- ==================================================================== -->
<main class="main-content p-3 p-md-4 flex-grow-1 overflow-x-hidden">

    <!-- HEADER HALAMAN & TOMBOL AKSI -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 mb-0">Network Ports</h1>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#portModal" onclick="window.clearFormPort()">
            <i class="bi bi-ethernet me-1"></i> Tambah Port Baru
        </button>
    </div>

    <!-- NOTIFIKASI SYSTEM ALERT -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- AREA DATA TABEL UTAMA -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">ID</th>
                                    <th>VLAN Induk (Perangkat)</th>
                                    <th>Nomor Port</th>
                                    <th>Nama / Alokasi Port</th>
                                    <th>Status</th>
                                    <th class="text-center pe-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($ports) && count($ports) > 0): ?>
                                    <?php foreach ($ports as $row): ?>
                                        <tr>
                                            <td class="ps-3 fw-bold"><?= $row['id'] ?></td>
                                            <td>
                                                <span class="badge bg-secondary px-2 py-1">
                                                    VLAN <?= htmlspecialchars($row['vlan'] ?? 'Unknown') ?>
                                                </span>
                                            </td>
                                            <td class="fw-medium text-primary"><?= htmlspecialchars($row['port']) ?></td>
                                            <td><?= htmlspecialchars($row['nama'] ?? '-') ?></td>
                                            <td>
                                                <span class="badge <?= $row['status'] == 1 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> rounded-pill px-3">
                                                    <?= $row['status'] == 1 ? 'Aktif' : 'Non-Aktif' ?>
                                                </span>
                                            </td>
                                            <td class="text-center pe-3">
                                                <!-- Tombol Edit (Ikon Pensil Kuning) -->
                                                <button type="button" class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#portModal" onclick='window.editPort(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <!-- Tombol Hapus (Ikon Sampah Merah) -->
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deletePortModal" onclick="window.setupDeletePort(<?= $row['id'] ?>, '<?= htmlspecialchars($row['port'], ENT_QUOTES) ?>')">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data network port ditemukan.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================================================================== -->
    <!-- INTERFACES MODAL FORM: TAMBAH & EDIT DATA PORT                       -->
    <!-- ==================================================================== -->
    <div class="modal fade" id="portModal" tabindex="-1" aria-labelledby="portModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <form id="portForm" action="network_port.php" method="POST">
                    
                    <div class="modal-header border-bottom-0 pt-3 px-4">
                        <h5 class="modal-title fw-bold text-dark" id="portModalLabel">Tambah Port Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body px-4 py-2">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="portId">

                        <div class="row g-3">
                            <!-- Dropdown Perangkat / VLAN Induk -->
                            <div class="col-12">
                                <label for="portDevice" class="form-label small fw-bold text-secondary">Perangkat (VLAN Induk)</label>
                                <select name="network_device_id" id="portDevice" class="form-select" required>
                                    <option value="" disabled selected>-- Pilih Perangkat / VLAN --</option>
                                    <?php
                                    if (isset($listDevices)) {
                                        foreach ($listDevices as $device) {
                                            echo "<option value='{$device['id']}'>ID Perangkat: {$device['id']} - VLAN {$device['vlan']}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Input Nomor/Nama Port -->
                            <div class="col-12">
                                <label for="portCode" class="form-label small fw-bold text-secondary">Nomor / Identitas Port (Maks. 20 Karakter)</label>
                                <input type="text" name="port" id="portCode" class="form-control" placeholder="Contoh: Ge0/1, Port 24" maxlength="20" required>
                            </div>

                            <!-- Input Alokasi Nama -->
                            <div class="col-12">
                                <label for="portNama" class="form-label small fw-bold text-secondary">Nama Alokasi / Deskripsi Port</label>
                                <input type="text" name="nama" id="portNama" class="form-control" placeholder="Contoh: Jalur Server, Uplink Core Sw" maxlength="100">
                            </div>

                            <!-- Dropdown Status -->
                            <div class="col-12">
                                <label for="portStatus" class="form-label small fw-bold text-secondary">Status Port</label>
                                <select name="status" id="portStatus" class="form-select" required>
                                    <option value="1">Aktif / Connected</option>
                                    <option value="0">Non-Aktif / Disconnected</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer border-top-0 pb-3 px-4">
                        <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary fw-bold px-4 shadow-sm">Simpan Port</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==================================================================== -->
    <!-- INTERFACES MODAL FORM: KONFIRMASI SECURITY HAPUS PORT               -->
    <!-- ==================================================================== -->
    <div class="modal fade" id="deletePortModal" tabindex="-1" aria-labelledby="deletePortModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <form action="network_port.php" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_form_id">

                    <div class="modal-body text-center p-4">
                        <div class="text-danger mb-3">
                            <i class="bi bi-exclamation-triangle-fill" style="font-size: 3.5rem;"></i>
                        </div>
                        <h5 class="fw-bold text-dark" id="deletePortModalLabel">Hapus Port?</h5>
                        <p class="text-muted small px-2 mt-2">
                            Apakah Anda yakin ingin menghapus data <span id="delete_port_code" class="fw-bold text-dark"></span> secara permanen? Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                    <div class="modal-footer border-top-0 d-flex justify-content-center pb-4 pt-0 gap-2">
                        <button type="button" class="btn btn-sm btn-light border px-4 fw-medium" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-danger fw-bold px-4 shadow-sm">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==================================================================== -->
    <!-- CONTROLLER SCRIPT JAVASCRIPT: PENGENDALI MODAL CRUD NETWORK PORT     -->
    <!-- ==================================================================== -->
    <script>
    // 1. Fungsi Reset Isian Form Modal Port
    window.clearFormPort = function() {
        const form = document.getElementById('portForm'); 
        if (form) form.reset();
        if (document.getElementById('formAction')) document.getElementById('formAction').value = 'create';
        if (document.getElementById('portId')) document.getElementById('portId').value = '';
        if (document.getElementById('portModalLabel')) document.getElementById('portModalLabel').innerText = 'Tambah Port Baru';
    };

    // 2. Fungsi Otomatis Mengisi Form Modal Saat Edit (Pensil Kuning)
    window.editPort = function(data) {
        window.clearFormPort();
        if (document.getElementById('formAction')) document.getElementById('formAction').value = 'update';
        if (document.getElementById('portId')) document.getElementById('portId').value = data.id;
        if (document.getElementById('portModalLabel')) document.getElementById('portModalLabel').innerText = 'Edit Data Port (ID: ' + data.id + ')';
        
        if (document.getElementById('portDevice')) document.getElementById('portDevice').value = data.network_device_id;
        if (document.getElementById('portCode')) document.getElementById('portCode').value = data.port;
        if (document.getElementById('portNama')) document.getElementById('portNama').value = data.nama;
        if (document.getElementById('portStatus')) document.getElementById('portStatus').value = data.status;
    };

    // 3. Fungsi Menembakkan Parameter ID & Nomor Port ke Modal Hapus
    window.setupDeletePort = function(id, port) {
        if (document.getElementById('delete_form_id')) {
            document.getElementById('delete_form_id').value = id;
        }
        if (document.getElementById('delete_port_code')) {
            document.getElementById('delete_port_code').innerText = 'Port "' + port + '"';
        }
    };
    </script>

    <?php include 'footer-admin.php'; ?>
