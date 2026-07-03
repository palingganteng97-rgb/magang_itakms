<?php
// ====================================================================
// LOGIKA BACKEND UTAMA - INSTANT REDIRECT (TANPA ALERT POP-UP)
// ====================================================================

require_once __DIR__ . '/auth.php';
require_login(); 
require_once __DIR__ . '/db.php'; 

$currentPage = 'backup_jobs.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // === PROSES TAMBAH DATA (CREATE) ===
    if (isset($_POST['action']) && $_POST['action'] == 'create') {
        $server_id = $_POST['server_id'];
        $lokasi    = $_POST['lokasi'];
        $jadwal    = $_POST['jadwal'];
        $status    = isset($_POST['status']) ? $_POST['status'] : 1;

        try {
            $sql = "INSERT INTO backup_jobs (server_id, lokasi, jadwal, status, backup_terakhir) VALUES (?, ?, ?, ?, NULL)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$server_id, $lokasi, $jadwal, $status]);
            
            echo "<script>window.location.href = 'backup_jobs.php';</script>";
            exit();
        } catch (Exception $e) {
            $message = "Gagal menambah Backup Job: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // === PROSES UBAH DATA (UPDATE) ===
    if (isset($_POST['action']) && $_POST['action'] == 'update') {
        $id        = $_POST['id'];
        $server_id = $_POST['server_id'];
        $lokasi    = $_POST['lokasi'];
        $jadwal    = $_POST['jadwal'];
        $status    = isset($_POST['status']) ? $_POST['status'] : 1;

        try {
            $sql = "UPDATE backup_jobs SET server_id = ?, lokasi = ?, jadwal = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$server_id, $lokasi, $jadwal, $status, $id]);
            
            echo "<script>window.location.href = 'backup_jobs.php';</script>";
            exit();
        } catch (Exception $e) {
            $message = "Gagal memperbarui Backup Job: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// === PROSES HAPUS DATA (DELETE) ===
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        $sql = "DELETE FROM backup_jobs WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        
        echo "<script>window.location.href = 'backup_jobs.php';</script>";
        exit();
    } catch (Exception $e) {
        $message = "Gagal menghapus Backup Job: " . $e->getMessage();
        $message_type = "danger";
    }
}

// ====================================================================
// PEMBACAAN DATA AKHIR (SELECT DENGAN LEFT JOIN KELAS SERVERS)
// ====================================================================
try {
    $backup_jobs = $conn->query("SELECT bj.*, s.fungsi FROM backup_jobs bj LEFT JOIN servers s ON bj.server_id = s.id ORDER BY bj.id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $backup_jobs = [];
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
<main class="col-md-8 ms-sm-auto col-lg-9 px-md-4 pt-4 offset-md-4 offset-lg-3">
    <!-- KARTU DAN TABEL UTAMA BACKUP JOBS -->
    <div class="card shadow-sm border rounded-3">
        <!-- Header Konten Utama -->
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
          <h5 class="mb-0 text-dark fw-bold">
              <i class="bi bi-database-fill-gear text-primary me-2"></i> Jadwal Backup (Backup Jobs)
          </h5>
            <!-- Tombol pemicu Modal Tambah Data -->
            <button class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddBackup">
                <i class="bi bi-plus-lg me-1"></i> Tambah Job
            </button>
        </div>

        <!-- Body Utama Tempat Tabel Data -->
        <div class="card-body p-4">
            <!-- Notifikasi Status Berhasil/Gagal -->
            <?php if(!empty($message)): ?>
                <div class="alert alert-<?= $message_type; ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="bi <?= $message_type == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
                    <?= $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Tabel Bergaris Pemisah Jelas dengan Fitur Responsif Terkunci Kerapiannya -->
            <div class="table-responsive border rounded bg-white shadow-sm">
                <!-- Tambahan kelas .text-nowrap mengunci agar baris teks tidak patah ke bawah di mobile -->
                <table class="table table-bordered table-hover align-middle m-0 text-nowrap">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th style="width: 5%; min-width: 60px;" class="text-center">ID</th>
                            <th style="width: 15%; min-width: 120px;">Server ID</th>
                            <th style="width: 25%; min-width: 220px;">Lokasi Penyimpanan</th>
                            <th style="width: 20%; min-width: 180px;">Jadwal (Cron/Waktu)</th>
                            <th style="width: 20%; min-width: 180px;">Backup Terakhir</th>
                            <th style="width: 5%; min-width: 90px;" class="text-center">Status</th>
                            <th style="width: 10%; min-width: 100px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($backup_jobs) == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-folder-x display-6 d-block mb-2 text-secondary"></i> Belum ada data Backup Job yang tersimpan.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach($backup_jobs as $row): ?>
                            <tr>
                                <td class="text-center"><span class="text-muted fw-bold">#<?= $row['id']; ?></span></td>
                                <td class="fw-semibold text-dark">Server #<?= htmlspecialchars($row['server_id']); ?></td>
                                <td>
                                    <div class="d-inline-block text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($row['lokasi']); ?>">
                                        <span class="badge bg-light text-dark border px-2 py-1.5">
                                            <i class="bi bi-folder2-open me-1 text-secondary"></i><?= htmlspecialchars($row['lokasi']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted fw-semibold">
                                        <i class="bi bi-clock-history me-1"></i><?= htmlspecialchars($row['jadwal']); ?>
                                    </small>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= !empty($row['backup_terakhir']) ? date('d M Y H:i:s', strtotime($row['backup_terakhir'])) : '<span class="text-danger fw-semibold">Belum pernah</span>'; ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <?= $row['status'] == 1 ? '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Aktif</span>' : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Non-Aktif</span>'; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center">
                                        <div class="btn-group shadow-sm border rounded bg-white">
                                            <!-- Tombol Pemicu Modal Edit -->
                                            <button class="btn btn-sm text-warning border-0" data-bs-toggle="modal" data-bs-target="#modalEditBackup<?= $row['id']; ?>" title="Ubah Data">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <!-- Tombol Pemicu Modal Hapus -->
                                            <button class="btn btn-sm text-danger border-0 border-start" data-bs-toggle="modal" data-bs-target="#modalDeleteBackup<?= $row['id']; ?>" title="Hapus Data">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        </div>
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
<!-- TAHAP 2: MODAL TAMBAH DATA (SEJAJAR HORIZONTAL)                     -->
<!-- ==================================================================== -->
<div class="modal fade" id="modalAddBackup" tabindex="-1" aria-labelledby="modalAddBackupLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <form action="backup_jobs.php" method="POST">
          <!-- Hidden Input Action untuk Handler CRUD PHP -->
          <input type="hidden" name="action" value="create">
          
          <!-- Header Modal -->
          <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold text-primary" id="modalAddBackupLabel">
              <i class="bi bi-plus-circle-fill me-2"></i>Registrasi Tugas Backup Baru
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          
          <!-- Body Modal -->
          <div class="modal-body pt-3">
            
<!-- Baris Server ID (Dropdown PDO Terintegrasi Aman) -->
<div class="row mb-3 align-items-center">
    <label for="server_id" class="col-4 col-form-label small fw-bold text-secondary text-end">Server ID</label>
    <div class="col-8">
        <select id="server_id" name="server_id" class="form-select form-select-sm text-dark" required>
            <option value="" disabled selected>-- Pilih Server --</option>
            <?php
            try {
                // Diubah menggunakan nama kolom 'id' dan 'fungsi' sesuai skema tabel servers Anda
                $query_server = $conn->query("SELECT id, fungsi FROM servers ORDER BY id ASC");
                $servers = $query_server->fetchAll(PDO::FETCH_ASSOC);

                foreach ($servers as $server) {
                    echo "<option value='" . htmlspecialchars($server['id']) . "'>";
                    echo "ID " . htmlspecialchars($server['id']) . " - " . htmlspecialchars($server['fungsi'] ?? 'Tanpa Fungsi/Deskripsi');
                    echo "</option>";
                }
            } catch (Exception $e) {
                // Memunculkan detail pesan error asli jika query MySQL tersendat
                echo "<option value='' disabled>SQL Error: " . htmlspecialchars($e->getMessage()) . "</option>";
            }
            ?>
        </select>
    </div>
</div>

            <!-- Baris Lokasi Penyimpanan / Path -->
            <div class="row mb-3 align-items-center">
                <label for="lokasi" class="col-4 col-form-label small fw-bold text-secondary text-end">Lokasi / Path Tujuan</label>
                <div class="col-8">
                    <input type="text" id="lokasi" name="lokasi" class="form-control form-control-sm text-dark" maxlength="255" placeholder="Contoh: /var/www/backup/db_main" required>
                </div>
            </div>

            <!-- Baris Jadwal Eksekusi (VARCHAR 100) -->
            <div class="row mb-3 align-items-center">
                <label for="jadwal" class="col-4 col-form-label small fw-bold text-secondary text-end">Jadwal (Cron/Waktu)</label>
                <div class="col-8">
                    <input type="text" id="jadwal" name="jadwal" class="form-control form-control-sm text-dark" maxlength="100" placeholder="Contoh: * * * * * atau Setiap Jam" required>
                </div>
            </div>
            
            <!-- Baris Status Regulasi Awal (TINYINT) -->
            <div class="row mb-2 align-items-center">
                <label for="status" class="col-4 col-form-label small fw-bold text-secondary text-end">Status Aktivasi</label>
                <div class="col-8">
                    <select id="status" name="status" class="form-select form-select-sm" style="max-width: 200px;">
                        <option value="1">Aktif (Siap Jalan)</option>
                        <option value="0">Non-Aktif (Tangguhkan)</option>
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
<!-- TAHAP 3: MODAL EDIT DATA (SEJAJAR HORIZONTAL & DINAMIS TERISI DATA) -->
<!-- ==================================================================== -->
<div class="modal fade" id="modalEditBackup<?= $row['id']; ?>" tabindex="-1" aria-labelledby="modalEditBackupLabel<?= $row['id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
        <!-- Mengarah ke backup_jobs.php tanpa enctype karena tidak ada upload berkas -->
        <form action="backup_jobs.php" method="POST">
            <!-- Hidden Input Keperluan Handler CRUD PHP -->
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $row['id']; ?>">
            
            <!-- Header Modal Edit -->
            <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold text-dark" id="modalEditBackupLabel<?= $row['id']; ?>">
                <i class="bi bi-pencil-square text-warning me-2"></i> Ubah Backup Job #<?= $row['id']; ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Body Modal Edit (Sejajar Kesamping) -->
            <div class="modal-body pt-3">
            
<!-- Baris Server ID (PASTIKAN MENGGUNAKAN KODE INI) -->
<div class="row mb-3 align-items-center">
    <label for="server_id" class="col-4 col-form-label small fw-bold text-secondary text-end">Server ID</label>
    <div class="col-8">
        <select id="server_id" name="server_id" class="form-select form-select-sm text-dark" required>
            <option value="" disabled selected>-- Pilih Server --</option>
            <?php
            try {
                // Diubah menggunakan kolom 'id' dan 'fungsi' sesuai skema asli HeidiSQL Anda
                $query_server = $conn->query("SELECT id, fungsi FROM servers ORDER BY id ASC");
                $servers = $query_server->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($servers)) {
                    foreach ($servers as $server) {
                        echo "<option value='" . htmlspecialchars($server['id']) . "'>";
                        echo "ID " . htmlspecialchars($server['id']) . " - " . htmlspecialchars($server['fungsi'] ?? 'Tanpa Deskripsi');
                        echo "</option>";
                    }
                } else {
                    echo "<option value='' disabled>Tabel servers kosong di database</option>";
                }
            } catch (Exception $e) {
                // Jika masih gagal, opsi di bawah ini akan memunculkan pesan error teknis MySQL aslinya
                echo "<option value='' disabled>Error: " . htmlspecialchars($e->getMessage()) . "</option>";
            }
            ?>
        </select>
    </div>
</div>

            <!-- Baris Ubah Lokasi Penyimpanan / Path (VARCHAR 255) -->
            <div class="row mb-3 align-items-center">
                <label for="lokasi_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-end">Lokasi / Path Tujuan</label>
                <div class="col-8">
                    <input type="text" id="lokasi_<?= $row['id']; ?>" name="lokasi" class="form-control form-control-sm text-dark" maxlength="255" value="<?= htmlspecialchars($row['lokasi']); ?>" required>
                </div>
            </div>
            
            <!-- Baris Ubah Jadwal Eksekusi (VARCHAR 100) -->
            <div class="row mb-3 align-items-center">
                <label for="jadwal_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-end">Jadwal (Cron/Waktu)</label>
                <div class="col-8">
                    <input type="text" id="jadwal_<?= $row['id']; ?>" name="jadwal" class="form-control form-control-sm text-dark" maxlength="100" value="<?= htmlspecialchars($row['jadwal']); ?>" required>
                </div>
            </div>

            <!-- Baris Informasi Backup Terakhir (Read-Only / Hanya Tampilan) -->
            <div class="row mb-3 align-items-center">
                <label class="col-4 col-form-label small fw-bold text-secondary text-end">Backup Terakhir</label>
                <div class="col-8">
                    <span class="small text-muted bg-light border p-1 px-2 rounded d-inline-block">
                        <i class="bi bi-clock-history me-1"></i>
                        <?= !empty($row['backup_terakhir']) ? date('d M Y H:i:s', strtotime($row['backup_terakhir'])) : 'Belum pernah berjalan'; ?>
                    </span>
                </div>
            </div>
            
            <!-- Baris Ubah Status Aktivasi (TINYINT) -->
            <div class="row mb-2 align-items-center">
                <label for="status_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-end">Status</label>
                <div class="col-8">
                    <select id="status_<?= $row['id']; ?>" name="status" class="form-select form-select-sm" style="max-width: 200px;">
                        <option value="1" <?= $row['status'] == 1 ? 'selected' : ''; ?>>Aktif</option>
                        <option value="0" <?= $row['status'] == 0 ? 'selected' : ''; ?>>Non-Aktif</option>
                    </select>
                </div>
            </div>
            </div>
            
            <!-- Footer Tombol Aksi -->
            <div class="modal-footer border-top-0 pt-0">
            <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Tutup</button>
            <button type="submit" class="btn btn-sm btn-warning fw-bold text-white px-4 shadow-sm">Update Tugas</button>
            </div>
        </form>
    </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL HAPUS DATA (BACKUP - HORIZONTAL)     -->
<!-- ========================================== -->
<div class="modal fade" id="modalDeleteBackup<?= $row['id']; ?>" tabindex="-1" aria-labelledby="modalDeleteBackupLabel<?= $row['id']; ?>" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      
      <!-- Header Modal -->
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-danger" id="modalDeleteBackupLabel<?= $row['id']; ?>">
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
                <p class="mb-1 text-secondary small fw-bold">Anda akan menghapus tugas backup berikut:</p>
                <h6 class="fw-bold text-dark mb-0">"Job #<?= $row['id']; ?> - <?= htmlspecialchars($row['lokasi']); ?>"</h6>
                <p class="text-muted small mt-2 mb-0">Tindakan ini bersifat permanen. Jadwal otomatis beserta seluruh konfigurasi job ini akan dihapus sepenuhnya dari sistem.</p>
            </div>
        </div>
      </div>
      
      <!-- Footer Tombol Aksi -->
      <div class="modal-footer border-top-0 pt-2">
        <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Batal</button>
        <!-- Mengarah ke backup_jobs.php dengan parameter delete ID -->
        <a href="backup_jobs.php?delete=<?= $row['id']; ?>" class="btn btn-sm btn-danger fw-bold px-4 shadow-sm">
            <i class="bi bi-trash me-1"></i>Ya, Hapus Data
        </a>
      </div>
      
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

// MODUL PENUTUP OTOMATIS ALERT & PEMBERSIH PARAMETER URL
document.addEventListener("click", function(t) {
    let alertBtn = t.target.closest('[data-bs-dismiss="alert"]');
    if (alertBtn) {
        let alertBox = t.target.closest('.alert');
        if (alertBox) {
            t.preventDefault();
            alertBox.remove(); 
            // Otomatis mengembalikan URL bersih ke backup_jobs.php tanpa parameter GET sisa
            window.location.href = window.location.pathname; 
        }
    }
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
