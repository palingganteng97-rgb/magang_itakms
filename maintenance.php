<?php
// =========================================================================
// 1. KONEKSI DATABASE & INISIALISASI (MENGGUNAKAN PDO SESUAI PROYEK ANDA)
// =========================================================================
require_once __DIR__ . '/auth.php';
require_login();

// Menyamakan kredensial dengan file dashboard.php Anda
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

$currentPage = 'maintenance.php';

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // =========================================================================
    // 2. PROSES AKSI FORM (TAMBAH / EDIT / HAPUS) VIA PDO
    // =========================================================================
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $asset_id = $_POST['asset_id'];
        $teknisi  = $_POST['teknisi'];
        $tanggal  = $_POST['tanggal'];
        $jenis    = $_POST['jenis'];
        $hasil    = $_POST['hasil'];
        $biaya    = $_POST['biaya'];
        $status   = $_POST['status'];

        if ($action == 'add') {
            $stmt = $conn->prepare("INSERT INTO maintenance_logs (asset_id, teknisi, tanggal, jenis, hasil, biaya, status) 
                                    VALUES (:asset_id, :teknisi, :tanggal, :jenis, :hasil, :biaya, :status)");
            $stmt->execute([
                ':asset_id' => $asset_id, ':teknisi' => $teknisi, ':tanggal' => $tanggal,
                ':jenis' => $jenis, ':hasil' => $hasil, ':biaya' => $biaya, ':status' => $status
            ]);
            header("Location: maintenance.php?status=success_add");
            exit;
        } elseif ($action == 'edit') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("UPDATE maintenance_logs SET 
                                    asset_id = :asset_id, teknisi = :teknisi, tanggal = :tanggal, 
                                    jenis = :jenis, hasil = :hasil, biaya = :biaya, status = :status 
                                    WHERE id = :id");
            $stmt->execute([
                ':asset_id' => $asset_id, ':teknisi' => $teknisi, ':tanggal' => $tanggal,
                ':jenis' => $jenis, ':hasil' => $hasil, ':biaya' => $biaya, ':status' => $status, ':id' => $id
            ]);
            header("Location: maintenance.php?status=success_edit");
            exit;
        }
    }

    if ($action == 'delete' && isset($_GET['id'])) {
        $id = $_GET['id'];
        $stmt = $conn->prepare("DELETE FROM maintenance_logs WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header("Location: maintenance.php?status=success_delete");
        exit;
    }

    // =========================================================================
    // 3. PENGAMBILAN DATA (SELECT) VIA PDO (PERBAIKAN KOLOM ASSET)
    // =========================================================================
    
    // A. Ambil data spesifik saat tombol Edit diklik
    $editData = null;
    if ($action == 'edit' && isset($_GET['id'])) {
        $id = $_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM maintenance_logs WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $editData = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // B. Ambil semua riwayat maintenance untuk tabel (Disederhanakan tanpa a.nama_asset agar tidak error)
    $stmtLogs = $conn->query("SELECT ml.*, u.nama AS nama_teknisi 
                              FROM maintenance_logs ml
                              LEFT JOIN users u ON ml.teknisi = u.id 
                              ORDER BY ml.tanggal DESC");
    $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

    // C. Ambil data master untuk pilihan dropdown select option
    // Kita ambil seluruh kolom (*) agar aman apa pun nama kolomnya di database Anda
    $assets = $conn->query("SELECT * FROM assets")->fetchAll(PDO::FETCH_ASSOC);
    $teknisis = $conn->query("SELECT id, nama FROM users")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Koneksi / Query Bermasalah: " . $e->getMessage());
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
        <a href="daily_checklist.php" class="nav-link <?= checkActiveMenu('daily_checklist.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-card-checklist me-3"></i> Daily Checklist</a>
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

<!-- AREA UTAMA UNTUK MAIN KONTEN -->
<main class="col-12 col-md-8 col-lg-9 ms-md-auto p-3 p-md-4" style="overflow-x: hidden;">
    
    <!-- Judul Halaman & Tombol Tambah Log -->
    <div class="row align-items-start align-items-md-center pt-3 pb-2 mb-3 border-bottom g-3">
        <div class="col-12 col-md-auto flex-grow-1 text-start">
            <h1 class="h2 mb-0 fw-bold text-dark text-wrap text-md-nowrap">
                <i class="bi bi-wrench-adjustable-circle text-primary me-2"></i> Log Pemeliharaan (Maintenance)
            </h1>
        </div>
        <div class="col-12 col-md-auto text-start text-md-end">
            <button type="button" class="btn btn-primary fw-bold shadow-sm w-100 text-nowrap" data-bs-toggle="modal" data-bs-target="#modalMaintenance" style="max-width: 200px;">
                <i class="bi bi-plus-lg me-1"></i> Tambah Log Baru
            </button>
        </div>
    </div>

    <!-- PENTING: PASTIKAN BLOK NOTIFIKASI INI ADA DI SINI -->
    <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div>
                <?php
                    if ($_GET['status'] == 'success_add') echo 'Data log baru berhasil disimpan!';
                    elseif ($_GET['status'] == 'success_edit') echo 'Data log berhasil diperbarui!';
                    elseif ($_GET['status'] == 'success_delete') echo 'Data log berhasil dihapus!';
                ?>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- 2. KONTEN TABEL JADI LEBAR PENUH (FULL WIDTH) -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white fw-bold py-3">
                    📋 Daftar Riwayat Aktivitas Maintenance
                </div>
                <!-- Pembungkus responsif agar tabel bisa digeser ke kanan-kiri secara internal di dalam HP -->
                <div class="card-body p-0 table-responsive">
                <!-- PERBAIKAN: Ditambahkan class 'table-bordered' untuk garis pemisah kolom & 'align-middle' agar teks tegak lurus -->
                <table class="table table-bordered table-striped table-hover mb-0 align-middle">
                    <thead class="table-light text-uppercase fs-7 text-secondary">
                        <!-- Tetap pertahankan text-nowrap agar judul kolom tidak turun ke bawah -->
                        <tr class="text-nowrap">
                            <th class="ps-3 text-center" style="width: 50px;">ID</th>
                            <th>Asset ID</th>
                            <th>Teknisi</th>
                            <th>Tanggal</th>
                            <th>Jenis Pemeliharaan</th>
                            <th>Biaya</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs) > 0): ?>
                            <?php foreach ($logs as $row): ?>
                                <tr>
                                    <td class="ps-3 text-center fw-bold text-secondary"><?= $row['id'] ?></td>
                                    <!-- Ditambahkan class text-nowrap pada isi data agar teks berjarak rapi dan tidak menempel -->
                                    <td class="text-nowrap">
                                        <span class="badge bg-secondary font-monospace">#<?= $row['asset_id'] ?></span>
                                    </td>
                                    <td class="text-nowrap"><?= htmlspecialchars($row['nama_teknisi'] ?? 'User ID: ' . $row['teknisi']) ?></td>
                                    <td class="text-nowrap"><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                    <!-- Berikan padding tambahan (px-3) agar teks jenis tidak menempel ke garis pembatas -->
                                    <td class="px-3 text-wrap" style="min-width: 150px; max-width: 250px;"><?= htmlspecialchars($row['jenis']) ?></td>
                                    <td class="text-nowrap fw-semibold">Rp <?= number_format($row['biaya'], 2, ',', '.') ?></td>
                                    <td class="text-center text-nowrap">
                                        <?php if ($row['status'] == 1): ?>
                                            <span class="badge bg-success-subtle text-success px-2.5 py-1.5 rounded"><i class="bi bi-check2 me-1"></i>Selesai</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis px-2.5 py-1.5 rounded"><i class="bi bi-clock me-1"></i>Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-3">
                                        <div class="btn-group" role="group">
                                            <a href="maintenance.php?action=edit&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit Data"><i class="bi bi-pencil-square"></i></a>
                                            <a href="maintenance.php?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus Data" onclick="return confirm('Apakah Anda yakin ingin menghapus log ini?');"><i class="bi bi-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-secondary py-5">
                                    <i class="bi bi-info-circle fs-3 d-block mb-2"></i> Belum ada data log pemeliharaan yang tersimpan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                <div class="card-footer text-muted text-center small">
                    Total Log: <?= count($logs) ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ========================================================================= -->
<!-- COMPONENTS: BOOTSTRAP MODAL FORM (MEMANJANG KE KANAN - LARGE MODAL)      -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalMaintenance" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modalMaintenanceLabel" aria-hidden="true">
    <!-- PERBAIKAN: Ditambahkan class 'modal-lg' agar ukuran jendela memanjang ke kanan -->
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="modalMaintenanceLabel">
                    <?= $editData ? '⚙️ Edit Data Log Maintenance' : '➕ Tambah Log Maintenance Baru' ?>
                </h5>
                <a href="maintenance.php" class="btn-close btn-close-white" aria-label="Close"></a>
            </div>
            <form action="maintenance.php?action=<?= $editData ? 'edit' : 'add' ?>" method="POST">
                <div class="modal-body p-4">
                    <?php if ($editData): ?>
                        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                    <?php endif; ?>

                    <!-- Grid Layout di dalam Modal agar kolom berjejer ke kanan -->
                    <div class="row">
                        
                        <!-- BARIS 1: ASSET & TEKNISI -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary">Pilih Asset</label>
                            <select name="asset_id" class="form-select" required>
                                <option value="">-- Pilih Asset --</option>
                                <?php foreach ($assets as $row): 
                                    $display_name = $row['nama'] ?? $row['asset_name'] ?? 'Asset ID: ' . $row['id'];
                                ?>
                                    <option value="<?= $row['id'] ?>" <?= ($editData && $editData['asset_id'] == $row['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($display_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary">Teknisi Penanggung Jawab</label>
                            <select name="teknisi" class="form-select" required>
                                <option value="">-- Pilih Teknisi --</option>
                                <?php foreach ($teknisis as $row): ?>
                                    <option value="<?= $row['id'] ?>" <?= ($editData && $editData['teknisi'] == $row['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($row['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- BARIS 2: TANGGAL & JENIS PEMELIHARAAN -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary">Tanggal Pemeliharaan</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= $editData ? $editData['tanggal'] : date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary">Jenis Pemeliharaan</label>
                            <input type="text" name="jenis" class="form-control" placeholder="Contoh: Pembersihan Port" value="<?= $editData ? htmlspecialchars($editData['jenis'] ?? '') : '' ?>" required>
                        </div>

                        <!-- BARIS 3: BIAYA & STATUS PENGERJAAN -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary">Biaya Pemeliharaan (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="0.01" name="biaya" class="form-control" value="<?= $editData ? $editData['biaya'] : '0.00' ?>" required>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary">Status Pengerjaan</label>
                            <select name="status" class="form-select" required>
                                <option value="1" <?= ($editData && $editData['status'] == 1) ? 'selected' : '' ?>>1 - Selesai (Success)</option>
                                <option value="2" <?= ($editData && $editData['status'] == 2) ? 'selected' : '' ?>>2 - Pending / Dalam Proses</option>
                            </select>
                        </div>

                        <!-- BARIS 4: KETERANGAN / HASIL (FULL WIDTH KIRI-KANAN) -->
                        <div class="col-12 mb-2">
                            <label class="form-label fw-semibold text-secondary">Hasil / Keterangan Masalah</label>
                            <textarea name="hasil" class="form-control" rows="3" placeholder="Tuliskan catatan teknis..." required><?= $editData ? htmlspecialchars($editData['hasil'] ?? '') : '' ?></textarea>
                        </div>

                    </div> <!-- Penutup .row internal modal -->
                </div>
                
                <div class="modal-footer bg-light">
                    <a href="maintenance.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary fw-bold">
                        <?= $editData ? 'Perbarui Data' : 'Simpan Log' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 1. MEMUAT FRAMEWORK UTAMA TERLEBIH DAHULU (PENTING) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- 2. KODE LOGIKA JAVASCRIPT KUSTOM -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Ambil elemen modal dari DOM
    const modalElement = document.getElementById('modalMaintenance');
    
    // Inisialisasi Modal Bootstrap tunggal yang valid
    const bsModal = new bootstrap.Modal(modalElement);

    // LOGIKA OTOMATIS: Buka modal secara visual jika PHP mendeteksi data edit
    <?php if ($editData): ?>
        bsModal.show();
    <?php endif; ?>

    // LOGIKA REDIRECT: Jika user menutup modal edit (klik silang/luar), bersihkan parameter URL
    modalElement.addEventListener('hidden.bs.modal', function () {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('action') === 'edit') {
            window.location.href = 'maintenance.php';
        }
    });

    // VALIDASI SISI KLIEN: Memberikan indikasi visual warna merah/hijau saat form dikirim
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
});
</script>

<!-- INTERAKSI DOM DAN SINKRONISASI POSISI SCROLL SIDEBAR UTAMA (KUNCI MUTLAK) -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const menuContainer = document.querySelector('.menu-scroll-container');
    const activeMenu = document.querySelector('.menu-scroll-container .active-style');
    
    // FIX MUTLAK: Selalu menetap mengunci fokus menu aktif saat halaman selesai dimuat (klik / reload)
    if (menuContainer && activeMenu) {
        const activeOffsetTop = activeMenu.offsetTop;
        menuContainer.scrollTop = activeOffsetTop - 20;
    }
});

// MODUL PENUTUP OTOMATIS ALERT & PEMBERSIH PARAMETER URL
document.addEventListener("click", function(t) {
    let alertBtn = t.target.closest('[data-bs-dismiss="alert"]');
    if (alertBtn) {
        let alertBox = t.target.closest('.alert');
        if (alertBox) {
            t.preventDefault();
            alertBox.remove(); 
            window.location.href = window.location.pathname; 
        }
    }
});
</script>

</body>
</html>
