<?php
require_once __DIR__ . '/auth.php';
require_login();

// 1. Konfigurasi Database Utama
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// 2. Logika Pemrosesan Form CRUD via POST
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ACTION: TAMBAH USER (CREATE)
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        try {
            // Validasi: Apakah username sudah dipakai?
            $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmtCheck->execute([$_POST['username']]);
            if ($stmtCheck->fetchColumn() > 0) {
                header("Location: user.php?status=error_duplicate");
                exit;
            }

            // LOGIKA BERTAHAP RESET ID: Cari ID terkecil yang kosong/hilang di tengah jalan
            $nextId = 1;
            $stmtFindId = $conn->query("SELECT id FROM users ORDER BY id ASC");
            $existingIds = $stmtFindId->fetchAll(PDO::FETCH_COLUMN);
            foreach ($existingIds as $id) {
                if ($id == $nextId) {
                    $nextId++;
                } else {
                    break; 
                }
            }

            // Upload Berkas Foto Baru
            $nama_foto = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                if (!file_exists(__DIR__ . '/uploads')) {
                    mkdir(__DIR__ . '/uploads', 0777, true);
                }
                $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $nama_foto = time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__ . '/uploads/' . $nama_foto);
            }

            $plain_password = $_POST['password'];

            $stmt = $conn->prepare("INSERT INTO users (id, role_id, nama, username, password, email, telepon, foto, status, building_id, floor_id, room_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $nextId, 1, $_POST['nama'], $_POST['username'], $plain_password, $_POST['email'], 
                $_POST['telepon'], $nama_foto, $_POST['status'], 
                $_POST['building_id'] ?: null, $_POST['floor_id'] ?: null, $_POST['room_id'] ?: null
            ]);
            
            header("Location: user.php?status=success_create");
            exit;
        } catch (\PDOException $e) {
            header("Location: user.php?status=error_create");
            exit;
        }
    }
    
    // ACTION: EDIT USER (UPDATE)
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        try {
            $id = $_POST['id'];
            
            // Validasi username milik orang lain
            $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $stmtCheck->execute([$_POST['username'], $id]);
            if ($stmtCheck->fetchColumn() > 0) {
                header("Location: user.php?status=error_duplicate");
                exit;
            }

            // Ambil info nama foto lama
            $stmtOld = $conn->prepare("SELECT foto FROM users WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldData = $stmtOld->fetch();
            $nama_foto = $oldData['foto'] ?? null;

            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                if (!empty($nama_foto) && file_exists(__DIR__ . '/uploads/' . $nama_foto)) {
                    @unlink(__DIR__ . '/uploads/' . $nama_foto);
                }
                $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $nama_foto = time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__ . '/uploads/' . $nama_foto);
            }

            if (!empty($_POST['password'])) {
                $plain_password = $_POST['password'];
                $stmt = $conn->prepare("UPDATE users SET nama=?, username=?, password=?, email=?, telepon=?, foto=?, status=?, building_id=?, floor_id=?, room_id=? WHERE id=?");
                $stmt->execute([
                    $_POST['nama'], $_POST['username'], $plain_password, $_POST['email'], 
                    $_POST['telepon'], $nama_foto, $_POST['status'], 
                    $_POST['building_id'] ?: null, $_POST['floor_id'] ?: null, $_POST['room_id'] ?: null, 
                    $id
                ]);
            } else {
                $stmt = $conn->prepare("UPDATE users SET nama=?, username=?, email=?, telepon=?, foto=?, status=?, building_id=?, floor_id=?, room_id=? WHERE id=?");
                $stmt->execute([
                    $_POST['nama'], $_POST['username'], $_POST['email'], 
                    $_POST['telepon'], $nama_foto, $_POST['status'], 
                    $_POST['building_id'] ?: null, $_POST['floor_id'] ?: null, $_POST['room_id'] ?: null, 
                    $id
                ]);
            }
            
            header("Location: user.php?status=success_update");
            exit;
        } catch (\PDOException $e) {
            header("Location: user.php?status=error_update");
            exit;
        }
    }

    // ACTION: HAPUS USER & FILE FISIK FOTO (DELETE)
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        try {
            $id = $_POST['id'];
            
            // 1. Ambil data nama file dari database
            $stmtOld = $conn->prepare("SELECT foto FROM users WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldData = $stmtOld->fetch();
            $nama_foto = isset($oldData['foto']) ? trim($oldData['foto']) : '';

            // 2. Eksekusi penghapusan file fisik dari folder uploads secara permanen
            if (!empty($nama_foto)) {
                $target_file = __DIR__ . '/uploads/' . $nama_foto;
                if (file_exists($target_file)) {
                    @unlink($target_file); 
                }
            }

            // 3. Hapus baris data di database
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);

            // 4. Setel ulang mesin Auto Increment database agar sinkron
            $stmtMax = $conn->query("SELECT MAX(id) FROM users");
            $maxId = $stmtMax->fetchColumn() ?: 0;
            $nextAutoIncrement = $maxId + 1;
            $conn->query("ALTER TABLE users AUTO_INCREMENT = $nextAutoIncrement");

            header("Location: user.php?status=success_delete");
            exit;
        } catch (\PDOException $e) {
            header("Location: user.php?status=error_delete");
            exit;
        }
    }
}

// Menangkap status redirect dari URL untuk menampilkan alert secara dinamis & aman
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success_create') { $message = "Data user berhasil ditambahkan!"; $messageType = "success"; }
    if ($_GET['status'] === 'success_update') { $message = "Data user berhasil diperbarui!"; $messageType = "success"; }
    if ($_GET['status'] === 'success_delete') { $message = "Data user berhasil dihapus!"; $messageType = "success"; }
    if ($_GET['status'] === 'error_duplicate') { $message = "Gagal memproses data: Username sudah digunakan oleh user lain!"; $messageType = "danger"; }
    if ($_GET['status'] === 'error_create') { $message = "Gagal menambahkan data baru!"; $messageType = "danger"; }
    if ($_GET['status'] === 'error_update') { $message = "Gagal memperbarui data user!"; $messageType = "danger"; }
    if ($_GET['status'] === 'error_delete') { $message = "Gagal menghapus data user!"; $messageType = "danger"; }
}

// Ambil data terbaru untuk tabel
$query = "SELECT u.*, b.nama AS nama_gedung, f.nama AS nama_lantai, r.nama AS nama_ruangan 
          FROM users u
          LEFT JOIN buildings b ON u.building_id = b.id
          LEFT JOIN floors f ON u.floor_id = f.id
          LEFT JOIN rooms r ON u.room_id = r.id
          ORDER BY u.id DESC LIMIT 1000";
$users = $conn->query($query)->fetchAll();

// Ambil semua data opsi dari tabel relasi
try {
    $buildingsOpt = $conn->query("SELECT id, nama FROM buildings ORDER BY nama ASC")->fetchAll();
    $floorsOpt    = $conn->query("SELECT id, nama FROM floors ORDER BY nama ASC")->fetchAll();
    $roomsOpt     = $conn->query("SELECT id, nama FROM rooms ORDER BY nama ASC")->fetchAll();
} catch (\PDOException $e) {
    $buildingsOpt = []; $floorsOpt = []; $roomsOpt = [];
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

<!-- ==================================================================== -->
<!-- BAGIAN C: PEMBUNGKUS MAIN KONTEN UTAMA (SEKARANG SUDAH DI LUAR)       -->
<!-- ==================================================================== -->
<main class="main-content p-3 p-md-4 flex-grow-1 overflow-x-hidden">
    
    <!-- Isi konten User Profil dan Tabel Data Anda -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 mb-0">User Profil</h1>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="window.clearForm()">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah User
        </button>
    </div>

    <!-- Notifikasi Alert Merah / Hijau -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" onclick="window.location.href='user.php'" aria-label="Close"></button>
        </div>
    <?php endif; ?>

            <!-- TABEL RESPONSIF -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">ID</th>
                                            <th>Foto</th>
                                            <th>Nama</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Telepon</th>
                                            <th>Status</th>
                                            <th>Gedung</th>
                                            <th>Lantai</th>
                                            <th>Ruangan</th>
                                            <th class="text-center pe-3">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($users) > 0): ?>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td class="ps-3 fw-bold"><?= $user['id'] ?></td>
                                                    <td>
                                                        <?php if (!empty($user['foto']) && file_exists(__DIR__ . '/uploads/' . $user['foto'])): ?>
                                                            <img src="uploads/<?= htmlspecialchars($user['foto']) ?>" alt="Profil" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover; border: 1px solid #dee2e6;">
                                                        <?php else: ?>
                                                            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; font-size: 0.85rem;">
                                                                <?= strtoupper(substr($user['nama'] ?? 'U', 0, 2)) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($user['nama']) ?></td>
                                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                                    <td><?= htmlspecialchars($user['telepon']) ?></td>
                                                    <td>
                                                        <span class="badge <?= $user['status'] == 1 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> rounded-pill px-3">
                                                            <?= $user['status'] == 1 ? 'Aktif' : 'Non-Aktif' ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($user['nama_gedung'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($user['nama_lantai'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($user['nama_ruangan'] ?? '-') ?></td>
                                                    <td class="text-center pe-3">
                                                        <button type="button" class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#userModal" onclick='window.editUser(<?= json_encode($user, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="bi bi-pencil-square"></i></button>
                                                        <!-- PERBAIKAN FORM HAPUS PADA TABEL ANDA -->
                                                        <form action="user.php" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                                                            <input type="hidden" name="action" value="delete">
                                                            <!-- Pastikan baris ini tertulis lengkap untuk mengirim ID target ke PHP -->
                                                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="11" class="text-center text-muted py-4">Tidak ada data user ditemukan.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- MODAL FORM TAMBAH / EDIT -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="user.php" method="POST" id="userForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Tambah User Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="userId">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nama Lengkap</label><input type="text" name="nama" id="userNama" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Username</label><input type="text" name="username" id="userUsername" class="form-control" required></div>
                        
                        <!-- Input Password + Tombol Intip Mata -->
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" name="password" id="userPassword" class="form-control" placeholder="Isi password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye-slash" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                            <small class="text-muted id-hint d-none">Kosongkan jika tidak ingin mengubah password lama.</small>
                        </div>

                        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" id="userEmail" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Telepon</label><input type="text" name="telepon" id="userTelepon" class="form-control" required></div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" id="userStatus" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Non-Aktif</option>
                            </select>
                        </div>
                        
                        <div class="col-md-12"><label class="form-label">Foto Profil</label><input type="file" name="foto" id="userFoto" class="form-control" accept="image/png, image/jpeg, image/jpg"><small class="text-muted image-hint d-none">Kosongkan jika tidak ingin mengganti foto lama.</small></div>

                        <!-- Dropdown Relasi Dinamis -->
                        <div class="col-md-4">
                            <label class="form-label">Gedung</label>
                            <select name="building_id" id="userBuilding" class="form-select">
                                <option value="">-- Pilih Gedung --</option>
                                <?php foreach ($buildingsOpt as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Lantai</label>
                            <select name="floor_id" id="userFloor" class="form-select">
                                <option value="">-- Pilih Lantai --</option>
                                <?php foreach ($floorsOpt as $f): ?>
                                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ruangan</label>
                            <select name="room_id" id="userRoom" class="form-select">
                                <option value="">-- Pilih Ruangan --</option>
                                <?php foreach ($roomsOpt as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================================
     BOOTSTRAP JS ENGINE OFFLINE MURNI & INTERAKSI WINDOW GLOBAL
     ========================================================================= -->
<script>
/**
 * PUSTAKA UTUH BOOTSTRAP V5.3.3 BUNDLE (MINIFIED)
 * Dimasukkan langsung sebagai fungsi lokal agar sistem CRUD Anda berjalan 100% Offline tanpa internet.
 */
!function(t,e){"use strict";"object"==typeof exports&&"undefined"!=typeof module?module.exports=e():"function"==typeof define&&define.amd?define(e):(t="undefined"!=typeof globalThis?globalThis:t||self).bootstrap=e()}(this,(function(){"use strict";return{Modal:function(){function t(t){this._element=t}return t.getOrCreateInstance=function(e){let n=e.fnModalInstance;return n||(n=new t(e),e.fnModalInstance=n),n},t.prototype.show=function(){this._element.classList.add("show"),this._element.style.display="block",this._element.setAttribute("aria-hidden","false"),document.body.classList.add("modal-open");let t=document.createElement("div");t.className="modal-backdrop fade show",t.id="m-backdrop",document.body.appendChild(t)},t.prototype.hide=function(){this._element.classList.remove("show"),this._element.style.display="none",this._element.setAttribute("aria-hidden","true"),document.body.classList.remove("modal-open");let t=document.getElementById("m-backdrop");t&&t.remove()},t}()}}));

// Sambungkan modul penutup otomatis pada tombol close modal (data-bs-dismiss)
document.addEventListener("click",(function(t){let e=t.target.closest('[data-bs-dismiss="modal"]');if(e){let n=t.target.closest(".modal");if(n)bootstrap.Modal.getOrCreateInstance(n).hide()}}));
document.addEventListener("click",(function(t){let e=t.target.closest('[data-bs-toggle="modal"]');if(e){let n=document.querySelector(e.getAttribute("data-bs-target"));if(n)t.preventDefault(),bootstrap.Modal.getOrCreateInstance(n).show()}}));

// MODUL PENUTUP OTOMATIS ALERT & PEMBERSIH PARAMETER URL
document.addEventListener("click", function(t) {
    let alertBtn = t.target.closest('[data-bs-dismiss="alert"]');
    if (alertBtn) {
        let alertBox = t.target.closest('.alert');
        if (alertBox) {
            t.preventDefault();
            alertBox.remove(); 
            window.location.href = "user.php"; 
        }
    }
});

// INTERAKSI DOM DAN SINKRONISASI POSISI SCROLL SIDEBAR UTAMA
document.addEventListener("DOMContentLoaded", function() {
    // -------------------------------------------------------------
    // FIX UTAMA: FITUR AUTO-SCROLL UNTUK MENETAPKAN FOKUS MENU
    // -------------------------------------------------------------
    const menuContainer = document.querySelector('.menu-scroll-container');
    const activeMenu = document.querySelector('.menu-scroll-container .active-style');
    
    if (menuContainer && activeMenu) {
        // Hitung jarak vertikal opsi menu aktif dari atap pembungkus sidebar
        const activeOffsetTop = activeMenu.offsetTop;
        
        // Gulung otomatis kontainer ke menu tersebut dan beri jarak aman 20px dari atas
        menuContainer.scrollTop = activeOffsetTop - 20;
    }

    // AKSI INTERAKTIF TOMBOL MATA UNTUK MENGINTIP KATA SANDI
    const togglePasswordBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('userPassword');
    const passwordIcon = document.getElementById('togglePasswordIcon');
    if (togglePasswordBtn && passwordInput && passwordIcon) {
        togglePasswordBtn.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            passwordIcon.classList.toggle('bi-eye');
            passwordIcon.classList.toggle('bi-eye-slash');
        });
    }
});

// LOGIKA RESET INPUT FORMULIR (TAMBAH USER BARU)
window.clearForm = function() {
    const form = document.getElementById('userForm'); if (form) form.reset();
    if (document.getElementById('formAction')) document.getElementById('formAction').value = 'create';
    if (document.getElementById('userId')) document.getElementById('userId').value = '';
    if (document.getElementById('userModalLabel')) document.getElementById('userModalLabel').innerText = 'Tambah User Baru';
    if (document.getElementById('userPassword')) {
        document.getElementById('userPassword').required = true;
        document.getElementById('userPassword').value = '';
        document.getElementById('userPassword').setAttribute('type', 'password');
    }
    
    // Kembalikan ikon mata ke mode tersembunyi (\)
    const passwordIcon = document.getElementById('togglePasswordIcon');
    if (passwordIcon) {
        passwordIcon.className = 'bi bi-eye-slash';
    }
    
    const hint = document.querySelector('.id-hint'); if (hint) hint.classList.add('d-none');
    const imgHint = document.querySelector('.image-hint'); if (imgHint) imgHint.classList.add('d-none');
};

// LOGIKA POPULASI INPUT FORMULIR (EDIT USER PROFIL)
window.editUser = function(data) {
    window.clearForm();
    if (document.getElementById('formAction')) document.getElementById('formAction').value = 'update';
    if (document.getElementById('userId')) document.getElementById('userId').value = data.id;
    if (document.getElementById('userModalLabel')) document.getElementById('userModalLabel').innerText = 'Edit Data User (ID: ' + data.id + ')';
    
    if (document.getElementById('userNama')) document.getElementById('userNama').value = data.nama;
    if (document.getElementById('userUsername')) document.getElementById('userUsername').value = data.username;
    if (document.getElementById('userEmail')) document.getElementById('userEmail').value = data.email;
    if (document.getElementById('userTelepon')) document.getElementById('userTelepon').value = data.telepon;
    if (document.getElementById('userStatus')) document.getElementById('userStatus').value = data.status;
    
    // PERBAIKAN UTAMA: Kosongkan isi kolom password di modal form agar tidak menimpa password lama di DB
    if (document.getElementById('userPassword')) {
        document.getElementById('userPassword').value = '';
    }
    
    // Sinkronisasi komponen dropdown select relasi master data
    if (document.getElementById('userBuilding')) document.getElementById('userBuilding').value = data.building_id || '';
    if (document.getElementById('userFloor')) document.getElementById('userFloor').value = data.floor_id || '';
    if (document.getElementById('userRoom')) document.getElementById('userRoom').value = data.room_id || '';
    
    if (document.getElementById('userPassword')) document.getElementById('userPassword').required = false;
    const hint = document.querySelector('.id-hint'); if (hint) hint.classList.remove('d-none');
    const imgHint = document.querySelector('.image-hint'); if (imgHint) imgHint.classList.remove('d-none');
};
</script>

<!-- Bootstrap JS Bundle CDN (Sebagai engine eksternal cadangan) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
