<?php
// ====================================================================
// LOGIKA BACKEND UTAMA - INSTANT REDIRECT (TANPA ALERT POP-UP)
// ====================================================================

require_once __DIR__ . '/auth.php';
require_login(); 
require_once __DIR__ . '/db.php'; 

$currentPage = 'sops.php';
$upload_dir = 'uploads/sops/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // === PROSES TAMBAH DATA (CREATE) ===
    if (isset($_POST['action']) && $_POST['action'] == 'create') {
        $category_id = $_POST['category_id'];
        $judul = $_POST['judul'];
        $isi = $_POST['isi'];
        $status = isset($_POST['status']) ? $_POST['status'] : 1;
        $lampiran_name = null;

        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
            $lampiran_name = time() . '_' . basename($_FILES['lampiran']['name']);
            move_uploaded_file($_FILES['lampiran']['tmp_name'], $upload_dir . $lampiran_name);
        }

        try {
            $sql = "INSERT INTO sops (category_id, judul, isi, lampiran, status) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$category_id, $judul, $isi, $lampiran_name, $status]);
            
            // Pengalihan instan tanpa memunculkan alert teks
            echo "<script>window.location.href = 'sops.php';</script>";
            exit();
        } catch (Exception $e) {
            $message = "Gagal menambah SOP: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // === PROSES UBAH DATA (UPDATE) ===
    if (isset($_POST['action']) && $_POST['action'] == 'update') {
        $id = $_POST['id'];
        $category_id = $_POST['category_id'];
        $judul = $_POST['judul'];
        $isi = $_POST['isi'];
        $status = isset($_POST['status']) ? $_POST['status'] : 1;
        $lampiran_name = $_POST['old_lampiran'];

        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
            $lampiran_name = time() . '_' . basename($_FILES['lampiran']['name']);
            move_uploaded_file($_FILES['lampiran']['tmp_name'], $upload_dir . $lampiran_name);
            if (!empty($_POST['old_lampiran']) && file_exists($upload_dir . $_POST['old_lampiran'])) {
                unlink($upload_dir . $_POST['old_lampiran']);
            }
        }

        try {
            $sql = "UPDATE sops SET category_id = ?, judul = ?, isi = ?, lampiran = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$category_id, $judul, $isi, $lampiran_name, $status, $id]);
            
            echo "<script>window.location.href = 'sops.php';</script>";
            exit();
        } catch (Exception $e) {
            $message = "Gagal memperbarui SOP: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// === PROSES HAPUS DATA (DELETE) ===
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        $stmt_file = $conn->prepare("SELECT lampiran FROM sops WHERE id = ?");
        $stmt_file->execute([$id]);
        $sop = $stmt_file->fetch();
        
        if ($sop) {
            if (!empty($sop['lampiran']) && file_exists($upload_dir . $sop['lampiran'])) {
                unlink($upload_dir . $sop['lampiran']);
            }
            
            $sql = "DELETE FROM sops WHERE id = ?";
            $conn->prepare($sql)->execute([$id]);
            
            echo "<script>window.location.href = 'sops.php';</script>";
            exit();
        }
    } catch (Exception $e) {
        $message = "Gagal menghapus SOP: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Pembacaan Data Akhir
$sops = $conn->query("SELECT sops.*, COALESCE(sop_categories.nama, 'Tanpa Kategori') as category_name 
                     FROM sops 
                     LEFT JOIN sop_categories ON sops.category_id = sop_categories.id 
                     ORDER BY sops.id DESC")->fetchAll(PDO::FETCH_ASSOC);

$categories = $conn->query("SELECT * FROM sop_categories ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
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
        <!-- SOPS (Menu Baru - Tampil Mobile & Desktop) --> 
        <li class="nav-item"> 
            <a href="sops.php" class="nav-link <?= ($currentPage == 'sops.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded d-flex align-items-center"> 
                <i class="bi bi-journal-text me-2"></i> <span>SOPS</span>
            </a> 
        </li> 
        <!-- SOP CATEGORIES (Menu Baru - Tampil Mobile & Desktop) --> 
        <li class="nav-item"> 
            <a href="sop_categories.php" class="nav-link active bg-primary <?= ($currentPage == 'sop_categories.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded d-flex align-items-center"> 
                <i class="bi bi-tags me-2"></i> <span>SOP Categories</span>
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
        <!-- SOPS (Menu Baru - Tampil Mobile & Desktop) --> 
        <li class="nav-item"> 
            <a href="sops.php" class="nav-link active bg-primary <?= ($currentPage == 'sops.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded d-flex align-items-center"> 
                <i class="bi bi-journal-text me-2"></i> <span>SOPS</span>
            </a> 
        </li> 
        <!-- SOP CATEGORIES (Menu Baru - Tampil Mobile & Desktop) --> 
        <li class="nav-item"> 
            <a href="sop_categories.php" class="nav-link <?= ($currentPage == 'sop_categories.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded d-flex align-items-center"> 
                <i class="bi bi-tags me-2"></i> <span>SOP Categories</span>
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

    <!-- KARTU DAN TABEL UTAMA SOPS -->
    <div class="card shadow-sm border rounded-3">
        <!-- Header Konten Utama -->
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="mb-0 text-dark fw-bold">
                <i class="bi bi-journal-text text-primary me-2"></i> Standard Operating Procedures (SOP)
            </h5>
            <!-- Tombol pemicu Modal Tambah Data (Tahap 2) -->
            <button class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddSOP">
                <i class="bi bi-plus-lg me-1"></i> Tambah SOP
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

            <!-- Tabel Bergaris Pemisah Jelas (table-bordered) -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle m-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th width="5%" class="text-center">ID</th>
                            <th width="25%">Judul SOP</th>
                            <th width="15%">Kategori</th>
                            <th width="30%">Isi Prosedur</th>
                            <th width="10%">Lampiran</th>
                            <th width="5%">Status</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($sops) == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-folder-x display-6 d-block mb-2 text-secondary"></i>
                                    Belum ada data SOP yang tersimpan di sistem.
                                </td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php foreach($sops as $row): ?>
                        <tr>
                            <td class="text-center"><span class="text-muted fw-bold">#<?= $row['id']; ?></span></td>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($row['judul']); ?></td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1.5">
                                    <?= htmlspecialchars($row['category_name']); ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted d-block text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($row['isi']); ?>">
                                    <?= htmlspecialchars($row['isi']); ?>
                                </small>
                            </td>
                            <td>
                                <?php if(!empty($row['lampiran'])): ?>
                                    <a href="<?= $upload_dir . $row['lampiran']; ?>" target="_blank" class="btn btn-sm btn-link text-decoration-none p-0 fw-semibold text-info">
                                        <i class="bi bi-file-earmark-arrow-down-fill me-1"></i>Buka File
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small"><i class="bi bi-dash"></i></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $row['status'] == 1 
                                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Aktif</span>' 
                                    : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Non-Aktif</span>'; 
                                ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group shadow-sm border rounded bg-white">
                                    <!-- Tombol Pemicu Modal Edit (Tahap 3) -->
                                    <button class="btn btn-sm text-warning border-0" data-bs-toggle="modal" data-bs-target="#modalEditSOP<?= $row['id']; ?>" title="Ubah Data">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <!-- Tombol Pemicu Modal Hapus (Tahap 3) -->
                                    <button class="btn btn-sm text-danger border-0 border-start" data-bs-toggle="modal" data-bs-target="#modalDeleteSOP<?= $row['id']; ?>" title="Hapus Data">
                                        <i class="bi bi-trash3-fill"></i>
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

<!-- ==================================================================== -->
<!-- TAHAP 2: MODAL TAMBAH DATA (SEJAJAR HORIZONTAL & DINAMIS KATEGORI)  -->
<!-- ==================================================================== -->
<div class="modal fade" id="modalAddSOP" tabindex="-1" aria-labelledby="modalAddSOPLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <form action="sops.php" method="POST" enctype="multipart/form-data">
          <!-- Hidden Input Action untuk Handler CRUD PHP -->
          <input type="hidden" name="action" value="create">
          
          <!-- Header Modal -->
          <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold text-primary" id="modalAddSOPLabel">
              <i class="bi bi-plus-circle-fill me-2"></i>Registrasi SOP Baru
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          
          <!-- Body Modal (Sejajar Kesamping) -->
          <div class="modal-body pt-3">
            
            <!-- Baris Judul SOP -->
            <div class="row mb-3 align-items-center">
                <label for="judul" class="col-4 col-form-label small fw-bold text-secondary text-end">Judul Prosedur</label>
                <div class="col-8">
                    <input type="text" id="judul" name="judul" class="form-control form-control-sm text-dark" placeholder="Contoh: Prosedur restart server" required>
                </div>
            </div>
            
            <!-- Baris Kategori Dinamis (Mengambil dari database sop_categories) -->
            <div class="row mb-3 align-items-center">
                <label for="category_id" class="col-4 col-form-label small fw-bold text-secondary text-end">Kategori</label>
                <div class="col-8">
                    <select id="category_id" name="category_id" class="form-select form-select-sm" required>
                        <option value="" disabled selected>-- Pilih Kategori --</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id']; ?>">
                                <!-- Fleksibel mendeteksi nama kolom database: nama atau nama_kategori -->
                                <?= htmlspecialchars($cat['nama'] ?? $cat['nama_kategori'] ?? 'Kategori #' . $cat['id']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Baris Detail Urutan Langkah Kerja -->
            <div class="row mb-3 align-items-start">
                <label for="isi" class="col-4 col-form-label small fw-bold text-secondary text-end pt-1">Langkah Kerja</label>
                <div class="col-8">
                    <textarea id="isi" name="isi" class="form-control form-control-sm text-dark" rows="5" placeholder="Tuliskan petunjuk teknis pelaksanaan tugas..." required></textarea>
                </div>
            </div>
            
            <!-- Baris Upload File Pendukung -->
            <div class="row mb-3 align-items-center">
                <label for="lampiran" class="col-4 col-form-label small fw-bold text-secondary text-end">File Pendukung</label>
                <div class="col-8">
                    <input type="file" id="lampiran" name="lampiran" class="form-control form-control-sm">
                </div>
            </div>
            
            <!-- Baris Status Regulasi Awal -->
            <div class="row mb-2 align-items-center">
                <label for="status" class="col-4 col-form-label small fw-bold text-secondary text-end">Status Awal</label>
                <div class="col-8">
                    <select id="status" name="status" class="form-select form-select-sm" style="max-width: 200px;">
                        <option value="1">Aktif (Langsung Terbitkan)</option>
                        <option value="0">Non-Aktif (Draft)</option>
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
<div class="modal fade" id="modalEditSOP<?= $row['id']; ?>" tabindex="-1" aria-labelledby="modalEditSOPLabel<?= $row['id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
        <form action="sops.php" method="POST" enctype="multipart/form-data">
            <!-- Hidden Input Keperluan Handler CRUD PHP -->
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $row['id']; ?>">
            <input type="hidden" name="old_lampiran" value="<?= $row['lampiran']; ?>">
            
            <!-- Header Modal Edit -->
            <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold text-dark" id="modalEditSOPLabel<?= $row['id']; ?>">
                <i class="bi bi-pencil-square text-warning me-2"></i> Ubah Data SOP
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Body Modal Edit (Selalu Sejajar Kesamping di Mobile & Desktop) -->
            <div class="modal-body pt-3">
            
            <!-- Baris Ubah Judul SOP -->
            <div class="row mb-3 align-items-center">
                <label for="judul_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-end">Judul SOP</label>
                <div class="col-8">
                    <input type="text" id="judul_<?= $row['id']; ?>" name="judul" class="form-control form-control-sm text-dark" value="<?= htmlspecialchars($row['judul']); ?>" required>
                </div>
            </div>
            
            <!-- Baris Ubah Kategori Terpilih -->
            <div class="row mb-3 align-items-center">
                <label for="category_id_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-end">Kategori</label>
                <div class="col-8">
                    <select id="category_id_<?= $row['id']; ?>" name="category_id" class="form-select form-select-sm" required>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id']; ?>" <?= $cat['id'] == $row['category_id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($cat['nama'] ?? $cat['nama_kategori'] ?? 'Kategori #' . $cat['id']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Baris Ubah Isi Prosedur Langkah Kerja -->
            <div class="row mb-3 align-items-start">
                <label for="isi_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-end pt-1">Isi Prosedur</label>
                <div class="col-8">
                    <textarea id="isi_<?= $row['id']; ?>" name="isi" class="form-control form-control-sm text-dark" rows="5" required><?= htmlspecialchars($row['isi']); ?></textarea>
                </div>
            </div>
            
            <!-- Baris Kelola Berkas Lampiran Aktif -->
            <div class="row mb-3 align-items-start">
                <label for="lampiran_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-end pt-1">Lampiran</label>
                <div class="col-8">
                    <input type="file" id="lampiran_<?= $row['id']; ?>" name="lampiran" class="form-control form-control-sm mb-1">
                    <?php if(!empty($row['lampiran'])): ?>
                        <div class="form-text text-muted bg-light p-1 px-2 rounded border d-inline-block small text-truncate" style="max-width: 100%;">
                            <i class="bi bi-file-earmark-check-fill text-success me-1"></i> File saat ini: 
                            <a href="<?= $upload_dir . $row['lampiran']; ?>" target="_blank" class="text-decoration-none text-info fw-semibold"><?= htmlspecialchars($row['lampiran']); ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Baris Ubah Status Regulasi -->
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
            <button type="submit" class="btn btn-sm btn-warning fw-bold text-white px-4 shadow-sm">Update SOP</button>
            </div>
        </form>
    </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL HAPUS DATA (SOP - HORIZONTAL)        -->
<!-- ========================================== -->
<div class="modal fade" id="modalDeleteSOP<?= $row['id']; ?>" tabindex="-1" aria-labelledby="modalDeleteSOPLabel<?= $row['id']; ?>" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      
      <!-- Header Modal -->
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-danger" id="modalDeleteSOPLabel<?= $row['id']; ?>">
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
                <p class="mb-1 text-secondary small fw-bold">Anda akan menghapus dokumen berikut:</p>
                <h6 class="fw-bold text-dark mb-0">"<?= htmlspecialchars($row['judul']); ?>"</h6>
                <p class="text-muted small mt-2 mb-0">Tindakan ini bersifat permanen. Data beserta file lampiran yang tersimpan di server akan dihapus sepenuhnya.</p>
            </div>
        </div>
      </div>
      
      <!-- Footer Tombol Aksi -->
      <div class="modal-footer border-top-0 pt-2">
        <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Batal</button>
        <a href="sops.php?delete=<?= $row['id']; ?>" class="btn btn-sm btn-danger fw-bold px-4 shadow-sm">
            <i class="bi bi-trash me-1"></i>Ya, Hapus Data
        </a>
      </div>
      
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
