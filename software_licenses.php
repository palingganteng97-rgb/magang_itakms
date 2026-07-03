<?php
require_once __DIR__ . '/auth.php';
require_login();

// Pastikan session aktif untuk menampung flash message
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. KONFIGURASI DATABASE UTAMA (PDO)
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

$currentPage = 'software_licenses.php';

// 2. PROSES CRUD (CREATE, UPDATE, DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'create') {
            $stmt = $conn->prepare("INSERT INTO software_licenses (nama, vendor, license_key, expired_at, jumlah_license, digunakan, status) 
                                    VALUES (:nama, :vendor, :license_key, :expired_at, :jumlah_license, :digunakan, :status)");
            $stmt->execute([
                ':nama' => $_POST['nama'],
                ':vendor' => $_POST['vendor'],
                ':license_key' => $_POST['license_key'] ?: null,
                ':expired_at' => $_POST['expired_at'] ?: null,
                ':jumlah_license' => intval($_POST['jumlah_license']),
                ':digunakan' => intval($_POST['digunakan']),
                ':status' => intval($_POST['status'])
            ]);
            $_SESSION['crud_message'] = "success_Tambah data berhasil!";
        }

        if ($_POST['action'] === 'update') {
            $stmt = $conn->prepare("UPDATE software_licenses SET nama = :nama, vendor = :vendor, license_key = :license_key, 
                                    expired_at = :expired_at, jumlah_license = :jumlah_license, digunakan = :digunakan, status = :status 
                                    WHERE id = :id");
            $stmt->execute([
                ':id' => intval($_POST['id']),
                ':nama' => $_POST['nama'],
                ':vendor' => $_POST['vendor'],
                ':license_key' => $_POST['license_key'] ?: null,
                ':expired_at' => $_POST['expired_at'] ?: null,
                ':jumlah_license' => intval($_POST['jumlah_license']),
                ':digunakan' => intval($_POST['digunakan']),
                ':status' => intval($_POST['status'])
            ]);
            $_SESSION['crud_message'] = "success_Ubah data berhasil!";
        }

        if ($_POST['action'] === 'delete') {
            $stmt = $conn->prepare("DELETE FROM software_licenses WHERE id = :id");
            $stmt->execute([':id' => intval($_POST['id'])]);
            $_SESSION['crud_message'] = "danger_Data berhasil dihapus!";
        }
        
        // REDIRECT setelah sukses untuk membersihkan data POST browser
        header("Location: software_licenses.php");
        exit();

    } catch (\PDOException $e) {
        $_SESSION['crud_message'] = "danger_Gagal memproses data: " . $e->getMessage();
        header("Location: software_licenses.php");
        exit();
    }
}

// 3. READ DATA
try {
    $stmt = $conn->query("SELECT * FROM software_licenses ORDER BY id DESC");
    $licenses = $stmt->fetchAll();
} catch (\PDOException $e) {
    die("Gagal mengambil data: " . $e->getMessage());
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

<!-- AREA UTAMA KONTEN (Gunakan pembungkus ini agar susunan halaman tidak bergeser tertimpa sidebar) -->
<main class="col-md-8 ms-sm-auto col-lg-9 px-md-4 pt-4 offset-md-4 offset-lg-3">
    
    <!-- Notifikasi Feedback Sukses / Gagal -->
    <?php if(!empty($message)): 
        list($type, $text) = explode('_', $message); ?>
        <div class="alert alert-<?= $type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($text) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Bagian Kepala Halaman (Header Konten) -->
    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-key-fill me-2"></i> Kelola Lisensi Software</h1>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalLicense" onclick="setupModal('create')">
            <i class="bi bi-plus-lg me-1"></i> Tambah Lisensi
        </button>
    </div>

    <!-- Tabel Data Utama -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 5%;">No</th>
                            <th>Nama Software</th>
                            <th>Vendor</th>
                            <th>License Key</th>
                            <th>Masa Berlaku</th>
                            <th class="text-center">Kapasitas</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" style="width: 15%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach($licenses as $row): ?>
                        <tr>
                            <td class="ps-3"><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($row['nama'] ?? '') ?></strong></td>
                            <td><?= htmlspecialchars($row['vendor'] ?? '') ?></td>
                            <td><code><?= htmlspecialchars($row['license_key'] ?? '-') ?></code></td>
                            <td><?= (!empty($row['expired_at'])) ? date('d M Y', strtotime($row['expired_at'])) : '-' ?></td>
                            <td class="text-center">
                                <span class="badge bg-secondary"><?= $row['digunakan'] ?> / <?= $row['jumlah_license'] ?></span>
                            </td>
                            <td class="text-center">
                                <?= ($row['status'] == 1) ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Expired</span>' ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalLicense" 
                                    onclick="setupModal('update', <?= htmlspecialchars(json_encode($row)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <!-- Tombol Hapus Baru yang memicu Modal Bootstrap -->
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalDelete" 
                                            onclick="setupDeleteModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- MODAL INPUT & EDIT DATA LISENSI (LAYOUT HORIZONTAL) -->
<div class="modal fade" id="modalLicense" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <!-- Menggunakan modal-lg agar boks modal melebar ke kanan -->
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="" method="POST" class="modal-content">
            <!-- Hidden Input untuk Aksi CRUD & ID -->
            <input type="hidden" name="action" id="form_action" value="create">
            <input type="hidden" name="id" id="form_id">
            
            <!-- Header Modal -->
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="modalTitle">Tambah Lisensi Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Isi Form Modal (Menggunakan Grid Sistem) -->
            <div class="modal-body">
                <div class="row g-3">
                    
                    <!-- BARIS 1: Nama Software & Vendor berdampingan -->
                    <div class="col-md-6">
                        <label for="form_nama" class="form-label fw-semibold">Nama Software <span class="text-danger">*</span></label>
                        <input type="text" name="nama" id="form_nama" class="form-control" placeholder="Contoh: Windows 11 Pro" required maxlength="150">
                    </div>
                    <div class="col-md-6">
                        <label for="form_vendor" class="form-label fw-semibold">Vendor <span class="text-danger">*</span></label>
                        <input type="text" name="vendor" id="form_vendor" class="form-control" placeholder="Contoh: Microsoft" required maxlength="150">
                    </div>
                    
                    <!-- BARIS 2: License Key (Melebar penuh) -->
                    <div class="col-12">
                        <label for="form_license_key" class="form-label fw-semibold">License Key</label>
                        <textarea name="license_key" id="form_license_key" class="form-control" rows="2" placeholder="Masukkan serial number / kode lisensi"></textarea>
                    </div>
                    
                    <!-- BARIS 3: Masa Berlaku, Jumlah Lisensi, Digunakan, dan Status berjajar ke kanan -->
                    <div class="col-md-3">
                        <label for="form_expired_at" class="form-label fw-semibold">Masa Berlaku</label>
                        <input type="date" name="expired_at" id="form_expired_at" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="form_jumlah_license" class="form-label fw-semibold">Jumlah Lisensi</label>
                        <input type="number" name="jumlah_license" id="form_jumlah_license" class="form-control" min="0" value="0">
                    </div>
                    <div class="col-md-3">
                        <label for="form_digunakan" class="form-label fw-semibold">Digunakan</label>
                        <input type="number" name="digunakan" id="form_digunakan" class="form-control" min="0" value="0">
                    </div>
                    <div class="col-md-3">
                        <label for="form_status" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" id="form_status" class="form-select" required>
                            <option value="1" selected>1 - Aktif</option>
                            <option value="2">2 - Expired</option>
                        </select>
                    </div>

                </div>
            </div>
            
            <!-- Tombol Aksi Modal -->
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm" id="btnSubmit">
                    <i class="bi bi-save me-1"></i> Simpan Data
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL KONFIRMASI HAPUS -->
<div class="modal fade" id="modalDelete" tabindex="-1" aria-labelledby="modalDeleteTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form action="" method="POST" class="modal-content">
            <!-- Hidden Input untuk Aksi CRUD & ID yang akan dihapus -->
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete_form_id">
            
            <div class="modal-body text-center p-4">
                <!-- Ikon Peringatan -->
                <div class="text-danger mb-3">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 3rem;"></i>
                </div>
                <h5 class="modal-title mb-2" id="modalDeleteTitle">Hapus Data?</h5>
                <p class="text-muted small mb-0">Apakah Anda yakin ingin menghapus lisensi <strong id="delete_software_name"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            
            <div class="modal-footer justify-content-center border-0 pt-0">
                <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger btn-sm px-3">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
/**
 * Mengatur isi form pada Modal Tambah / Ubah Lisensi
 * @param {string} mode - Berisi nilai 'create' atau 'update'
 * @param {Object|null} data - Objek data lisensi dari database jika modenya 'update'
 */
function setupModal(mode, data = null) {
    // 1. Tentukan aksi form (create / update)
    document.getElementById('form_action').value = mode;

    if (mode === 'create') {
        // 2. Set judul dan kosongkan nilai form untuk input baru
        document.getElementById('modalTitle').innerText = 'Tambah Lisensi Baru';
        document.getElementById('form_id').value = '';
        document.getElementById('form_nama').value = '';
        document.getElementById('form_vendor').value = '';
        document.getElementById('form_license_key').value = '';
        document.getElementById('form_expired_at').value = '';
        document.getElementById('form_jumlah_license').value = 0;
        document.getElementById('form_digunakan').value = 0;
        document.getElementById('form_status').value = 1; // Default: Aktif
    } else {
        // 3. Set judul dan isi form dengan data yang dipilih untuk proses edit
        document.getElementById('modalTitle').innerText = 'Ubah Data Lisensi';
        document.getElementById('form_id').value = data.id;
        document.getElementById('form_nama').value = data.nama;
        document.getElementById('form_vendor').value = data.vendor;
        document.getElementById('form_license_key').value = data.license_key || '';
        document.getElementById('form_expired_at').value = data.expired_at || '';
        document.getElementById('form_jumlah_license').value = data.jumlah_license;
        document.getElementById('form_digunakan').value = data.digunakan;
        document.getElementById('form_status').value = data.status;
    }
}

/**
 * Mengirimkan data ID dan Nama Software ke Modal Konfirmasi Hapus
 * @param {number|string} id - ID baris lisensi yang akan dihapus
 * @param {string} nama - Nama software yang akan dihapus untuk teks konfirmasi
 */
function setupDeleteModal(id, nama) {
    document.getElementById('delete_form_id').value = id;
    document.getElementById('delete_software_name').innerText = nama;
}
</script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
