<?php
// ====================================================================
// LOGIKA BACKEND UTAMA - DAILY CHECKLISTS (INSTANT REDIRECT)
// ====================================================================

require_once __DIR__ . '/auth.php';
require_login(); 
require_once __DIR__ . '/db.php'; 

// Sesuaikan nama file saat ini untuk menu aktif
$currentFile = 'daily_checklist.php';

$message = '';
$message_type = '';

// Ambil user_id dari session login Anda (sesuaikan dengan key session sistem Anda)
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // === PROSES TAMBAH DATA (CREATE) ===
    if (isset($_POST['action']) && $_POST['action'] == 'create') {
        $tanggal = $_POST['tanggal'];
        $item    = $_POST['item'];
        $status  = isset($_POST['status']) ? (int)$_POST['status'] : 0;

        try {
            $sql = "INSERT INTO daily_checklists (tanggal, item, status, user_id) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$tanggal, $item, $status, $current_user_id]);
            
            echo "<script>window.location.href = 'daily_checklist.php';</script>";
            exit();
        } catch (Exception $e) {
            $message = "Gagal menambah Checklist: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // === PROSES UBAH DATA (UPDATE) ===
    if (isset($_POST['action']) && $_POST['action'] == 'update') {
        $id      = $_POST['id'];
        $tanggal = $_POST['tanggal'];
        $item    = $_POST['item'];
        $status  = isset($_POST['status']) ? (int)$_POST['status'] : 0;

        try {
            $sql = "UPDATE daily_checklists SET tanggal = ?, item = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$tanggal, $item, $status, $id]);
            
            echo "<script>window.location.href = 'daily_checklist.php';</script>";
            exit();
        } catch (Exception $e) {
            $message = "Gagal memperbarui Checklist: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // === PROSES HAPUS DATA (DELETE VIA MODAL POST) ===
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $id = $_POST['id'];
        
        try {
            $sql = "DELETE FROM daily_checklists WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            
            echo "<script>window.location.href = 'daily_checklist.php';</script>";
            exit();
        } catch (Exception $e) {
            $message = "Gagal menghapus Checklist: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// ====================================================================
// PEMBACAAN DATA AKHIR (SELECT DATA DAILY CHECKLISTS)
// ====================================================================
try {
    $daily_checklists = $conn->query("SELECT * FROM daily_checklists ORDER BY tanggal DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $daily_checklists = [];
    $message = "Gagal memuat data database: " . $e->getMessage();
    $message_type = "danger";
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
        <a href="daily_checklist.php" class="nav-link <?= checkActiveMenu('daily_checklist.php', $currentFile) ?> rounded-end d-flex align-items-center">
            <i class="bi bi-card-checklist me-3"></i> Daily Checklist
        </a> 
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

<!-- AREA UTAMA KONTEN -->
<main class="col-md-8 ms-sm-auto col-lg-9 px-3 px-md-4 pt-4 offset-md-4 offset-lg-3">
    <!-- KARTU DAN TABEL UTAMA DAILY CHECKLIST -->
    <div class="card shadow-sm border-0 rounded-3">
        <!-- Header Konten Utama -->
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom border-light">
            <h5 class="mb-0 text-dark fw-bold">
                <i class="bi bi-card-checklist text-primary me-2"></i> Daily Checklist
            </h5>
            <!-- Tombol Tambah yang Adaptif di Mobile (Hanya Ikon + Teks Pendek) -->
            <button class="btn btn-primary btn-sm px-2 px-md-3 fw-semibold shadow-sm rounded-2" data-bs-toggle="modal" data-bs-target="#modalAddChecklist">
                <i class="bi bi-plus-lg me-md-1"></i><span class="d-none d-md-inline"> Tambah Item</span>
            </button>
        </div>

        <!-- Body Utama Tempat Tabel Data -->
        <div class="card-body p-0">
            <!-- Notifikasi Status Berhasil/Gagal -->
            <?php if(!empty($message)): ?>
                <div class="m-3 alert alert-<?= $message_type; ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="bi <?= $message_type == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
                    <?= $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Tabel Responsif yang Sejajar -->
            <div class="table-responsive">
                <table class="table table-hover align-middle m-0" style="width: 100%;">
                    <thead class="table-light text-muted small text-uppercase border-bottom">
                        <tr>
                            <!-- ID disembunyikan di mobile agar menghemat ruang layar HP -->
                            <th style="width: 8%;" class="text-center ps-3 d-none d-md-table-cell">ID</th>
                            <th style="width: 20%; min-width: 90px;" class="ps-3 ps-md-0">Tanggal</th>
                            <th style="width: 47%;">Item Kegiatan</th>
                            <th style="width: 15%;" class="text-center">Status</th>
                            <th style="width: 10%; min-width: 80px;" class="text-center pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($daily_checklists) == 0): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="bi bi-clipboard-x display-6 d-block mb-2 text-secondary"></i> Belum ada aktivitas hari ini.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach($daily_checklists as $row): ?>
                            <tr>
                                <!-- ID hanya tampil di PC/Tablet -->
                                <td class="text-center ps-3 d-none d-md-table-cell">
                                    <span class="text-muted small fw-bold">#<?= $row['id']; ?></span>
                                </td>
                                <!-- Tanggal yang adaptif (ukuran font mengecil di mobile) -->
                                <td class="ps-3 ps-md-0">
                                    <div class="text-dark small lh-sm">
                                        <i class="bi bi-calendar3 d-none d-md-inline me-1 text-muted"></i>
                                        <span class="d-md-none fw-semibold"><?= date('d/m/y', strtotime($row['tanggal'])); ?></span>
                                        <span class="d-none d-md-inline"><?= date('d M Y', strtotime($row['tanggal'])); ?></span>
                                    </div>
                                </td>
                                <!-- Deskripsi Kegiatan otomatis membungkus teks ke bawah (tidak merusak baris) -->
                                <td>
                                    <div class="text-wrap fw-semibold text-dark small text-break" style="max-width: 100%;">
                                        <?= htmlspecialchars($row['item']); ?>
                                    </div>
                                </td>
                                <!-- Status Badge yang ukurannya mengecil di mobile -->
                                <td class="text-center">
                                    <?= $row['status'] == 1 
                                        ? '<span class="badge bg-success-subtle text-success px-1.5 px-md-2 py-1 small-badge"><i class="bi bi-check2 me-md-1"></i><span class="d-none d-md-inline">Selesai</span></span>' 
                                        : '<span class="badge bg-warning-subtle text-warning px-1.5 px-md-2 py-1 small-badge"><i class="bi bi-hourglass-split me-md-1"></i><span class="d-none d-md-inline">Pending</span></span>'; 
                                    ?>
                                </td>
                                <!-- Aksi Tombol yang rapi dan sejajar di barisnya -->
                                <td class="text-center pe-3">
                                    <div class="d-inline-flex gap-1 justify-content-center w-100">
                                        <!-- Tombol Edit -->
                                        <button class="btn btn-sm btn-light text-warning border-0 p-1 p-md-2" data-bs-toggle="modal" data-bs-target="#modalEditChecklist<?= $row['id']; ?>" title="Ubah Data">
                                            <i class="bi bi-pencil-square fs-6"></i>
                                        </button>
                                        <!-- Tombol Hapus -->
                                        <button class="btn btn-sm btn-light text-danger border-0 p-1 p-md-2" data-bs-toggle="modal" data-bs-target="#modalDeleteChecklist<?= $row['id']; ?>" title="Hapus Data">
                                            <i class="bi bi-trash3 fs-6"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- ==================================================================== -->
<!-- TAHAP 2: MODAL TAMBAH DATA CHECKLIST (SEJAJAR HORIZONTAL)           -->
<!-- ==================================================================== -->
<div class="modal fade" id="modalAddChecklist" tabindex="-1" aria-labelledby="modalAddChecklistLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <form action="daily_checklist.php" method="POST">
          <!-- Hidden Input Action untuk Handler CRUD PHP -->
          <input type="hidden" name="action" value="create">
          
          <!-- Header Modal -->
          <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold text-primary" id="modalAddChecklistLabel">
              <i class="bi bi-plus-circle-fill me-2"></i>Registrasi Item Checklist Baru
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          
          <!-- Body Modal -->
          <div class="modal-body pt-3">
            
            <!-- Baris Tanggal Kegiatan -->
            <div class="row mb-3 align-items-center">
                <label for="tanggal" class="col-4 col-form-label small fw-bold text-secondary text-end">Tanggal Kegiatan</label>
                <div class="col-8">
                    <input type="date" id="tanggal" name="tanggal" class="form-control form-control-sm text-dark" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <!-- Baris Item Kegiatan / Deskripsi Tugas -->
            <div class="row mb-3 align-items-start">
                <label for="item" class="col-4 col-form-label small fw-bold text-secondary text-end pt-1">Item Kegiatan</label>
                <div class="col-8">
                    <textarea id="item" name="item" class="form-control form-control-sm text-dark" rows="3" placeholder="Contoh: Periksa kapasitas storage server utama dan log backup..." required></textarea>
                </div>
            </div>
            
            <!-- Baris Status Regulasi Awal (TINYINT) -->
            <div class="row mb-2 align-items-center">
                <label for="status" class="col-4 col-form-label small fw-bold text-secondary text-end">Status Kerja</label>
                <div class="col-8">
                    <select id="status" name="status" class="form-select form-select-sm" style="max-width: 200px;">
                        <option value="0" selected>Pending (Belum Selesai)</option>
                        <option value="1">Selesai</option>
                    </select>
                </div>
            </div>
          </div>
          
          <!-- Tombol Aksi Mandiri -->
          <div class="modal-footer border-top-0 pt-0">
            <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-sm btn-primary fw-bold px-4 shadow-sm">Simpan Sistem</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- ==================================================================== -->
<!-- TAHAP 3: MODAL EDIT DATA CHECKLIST (SEJAJAR HORIZONTAL & DINAMIS)   -->
<!-- ==================================================================== -->
<div class="modal fade" id="modalEditChecklist<?= $row['id']; ?>" tabindex="-1" aria-labelledby="modalEditChecklistLabel<?= $row['id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
        <form action="daily_checklist.php" method="POST">
            <!-- Hidden Input Keperluan Handler CRUD PHP -->
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $row['id']; ?>">
            
            <!-- Header Modal Edit -->
            <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold text-dark" id="modalEditChecklistLabel<?= $row['id']; ?>">
                <i class="bi bi-pencil-square text-warning me-2"></i> Ubah Item Checklist #<?= $row['id']; ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Body Modal Edit (Sejajar Kesamping) -->
            <div class="modal-body pt-3">
            
                <!-- Baris Tanggal Kegiatan -->
                <div class="row mb-3 align-items-center">
                    <label for="tanggal_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-end">Tanggal Kegiatan</label>
                    <div class="col-8">
                        <input type="date" id="tanggal_<?= $row['id']; ?>" name="tanggal" class="form-control form-control-sm text-dark" value="<?= $row['tanggal']; ?>" required>
                    </div>
                </div>

                <!-- Baris Item Kegiatan / Deskripsi Tugas -->
                <div class="row mb-3 align-items-start">
                    <label for="item_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-end pt-1">Item Kegiatan</label>
                    <div class="col-8">
                        <textarea id="item_<?= $row['id']; ?>" name="item" class="form-control form-control-sm text-dark" rows="3" required><?= htmlspecialchars($row['item']); ?></textarea>
                    </div>
                </div>
                
                <!-- Baris Informasi User ID Pembuat (Read-Only / Hanya Tampilan) -->
                <div class="row mb-3 align-items-center">
                    <label class="col-4 col-form-label small fw-bold text-secondary text-end">Petugas (User ID)</label>
                    <div class="col-8">
                        <span class="small text-muted bg-light border p-1 px-2 rounded d-inline-block">
                            <i class="bi bi-person me-1"></i> User ID #<?= htmlspecialchars($row['user_id']); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Baris Ubah Status Kerja (TINYINT) -->
                <div class="row mb-2 align-items-center">
                    <label for="status_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-end">Status</label>
                    <div class="col-8">
                        <select id="status_<?= $row['id']; ?>" name="status" class="form-select form-select-sm" style="max-width: 200px;">
                            <option value="0" <?= $row['status'] == 0 ? 'selected' : ''; ?>>Pending (Belum Selesai)</option>
                            <option value="1" <?= $row['status'] == 1 ? 'selected' : ''; ?>>Selesai</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Footer Tombol Aksi -->
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-sm btn-light border px-3" data-bs-toggle="modal">Tutup</button>
                <button type="submit" class="btn btn-sm btn-warning fw-bold text-white px-4 shadow-sm">Update Tugas</button>
            </div>
        </form>
    </div>
    </div>
</div>

<!-- ==================================================================== -->
<!-- MODAL HAPUS DATA CHECKLIST (POST METHOD - AMAN DAN SEJAJAR)        -->
<!-- ==================================================================== -->
<div class="modal fade" id="modalDeleteChecklist<?= $row['id']; ?>" tabindex="-1" aria-labelledby="modalDeleteChecklistLabel<?= $row['id']; ?>" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <form action="daily_checklist.php" method="POST">
          <!-- Hidden Input Keperluan Handler CRUD PHP -->
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $row['id']; ?>">
          
          <!-- Header Modal -->
          <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold text-danger" id="modalDeleteChecklistLabel<?= $row['id']; ?>">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          
          <!-- Body Modal Dengan Format Memanjang Kanan -->
          <div class="modal-body pt-3">
            <div class="row align-items-center">
                <!-- Sisi Kiri: Ikon Peringatan -->
                <div class="col-sm-2 text-center text-sm-end mb-3 mb-sm-0">
                    <i class="bi bi-trash3 text-danger display-6"></i>
                </div>
                <!-- Sisi Kanan: Teks Penjelasan Data -->
                <div class="col-sm-10">
                    <p class="mb-1 text-secondary small fw-bold">Anda akan menghapus item checklist berikut:</p>
                    <h6 class="fw-bold text-dark mb-0">"Item #<?= $row['id']; ?> - <?= htmlspecialchars($row['item']); ?>"</h6>
                    <p class="text-muted small mt-2 mb-0">Tindakan ini bersifat permanen. Rekam aktivitas checklist harian ini akan dihapus sepenuhnya dari database sistem.</p>
                </div>
            </div>
          </div>
          
          <!-- Footer Tombol Aksi -->
          <div class="modal-footer border-top-0 pt-2">
            <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-sm btn-danger fw-bold px-4 shadow-sm">
                <i class="bi bi-trash me-1"></i>Ya, Hapus Data
            </button>
          </div>
      </form>
    </div>
  </div>
</div>

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

// MODUL PENUTUP OTOMATIS ALERT & PEMBERSIH PARAMETER URL (DINAMIS UNTUK DAILY CHECKLIST)
document.addEventListener("click", function(t) {
    let alertBtn = t.target.closest('[data-bs-dismiss="alert"]');
    if (alertBtn) {
        let alertBox = t.target.closest('.alert');
        if (alertBox) {
            t.preventDefault();
            alertBox.remove(); 
            // Otomatis mengembalikan URL bersih sesuai file yang sedang diakses (daily_checklist.php)
            window.location.href = window.location.pathname; 
        }
    }
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
