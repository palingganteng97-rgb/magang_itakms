<?php
require_once __DIR__ . '/auth.php';
require_login();

// 1. Konfigurasi Koneksi Database
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

// Konfigurasi Pagination Tabel
$perPage = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ===================================================
    // LOGIKA PROSES AKSI FORM CRUD (POST & GET)
    // ===================================================

    // A. PROSES TAMBAH DATA PORT (Create)
    if (isset($_POST['action']) && $_POST['action'] == 'create') {
        $network_device_id = $_POST['network_device_id'];
        $port              = $_POST['port'];
        $nama              = $_POST['nama'];
        $status            = $_POST['status']; // Bernilai 1 atau 0 dari form

        $stmtInsert = $conn->prepare("INSERT INTO network_ports (network_device_id, port, nama, status) VALUES (?, ?, ?, ?)");
        $stmtInsert->execute([$network_device_id, $port, $nama, $status]);
        
        header("Location: network_port.php?status=success_add");
        exit;
    }

    // B. PROSES UBAH DATA PORT (Update)
    if (isset($_POST['action']) && $_POST['action'] == 'update') {
        $id                = $_POST['id'];
        $network_device_id = $_POST['network_device_id'];
        $port              = $_POST['port'];
        $nama              = $_POST['nama'];
        $status            = $_POST['status']; // Bernilai 1 atau 0 dari form

        $stmtUpdate = $conn->prepare("UPDATE network_ports SET network_device_id = ?, port = ?, nama = ?, status = ? WHERE id = ?");
        $stmtUpdate->execute([$network_device_id, $port, $nama, $status, $id]);
        
        header("Location: network_port.php?status=success_update");
        exit;
    }

    // C. PROSES HAPUS DATA PORT (Delete)
    if (isset($_GET['delete'])) {
        $idDelete = $_GET['delete'];

        $stmtDelete = $conn->prepare("DELETE FROM network_ports WHERE id = ?");
        $stmtDelete->execute([$idDelete]);
        
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

    // 3. Mengambil record data dari tabel network_ports + INNER JOIN ke network_devices untuk memunculkan info VLAN induknya
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
    <main class="col-12 col-md-8 col-lg-9 ms-sm-auto ms-md-auto px-md-4 pt-4 offset-md-4 offset-lg-3">

      <!-- Header Konten Utama (PERBAIKAN: Ditambahkan tag penutup </div> di akhir baris judul) -->
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 fs-4 fs-md-2">Dashboard Sistem - Network Ports</h1>
      </div> 

      <!-- Notifikasi Flash Status CRUD -->
      <?php if(isset($_GET['status'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
              if($_GET['status'] == 'success_add') echo '<i class="bi bi-check-circle-fill me-2"></i> Data port baru berhasil ditambahkan!';
              if($_GET['status'] == 'success_update') echo '<i class="bi bi-check-circle-fill me-2"></i> Konfigurasi port berhasil diperbarui!';
              if($_GET['status'] == 'success_delete') echo '<i class="bi bi-trash-fill me-2"></i> Data port berhasil dihapus!';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <!-- Tombol Pemicu Modal Tambah (C - Create) -->
      <div class="mb-3">
        <button class="btn btn-primary btn-sm btn-md-md" data-bs-toggle="modal" data-bs-target="#addPortModal">
          <i class="bi bi-plus-circle me-1"></i> Tambah Network Port
        </button>
      </div>

      <!-- Tabel Data Network Ports (R - Read) -->
      <div class="table-responsive w-100 bg-white p-2 p-md-3 rounded shadow-sm border" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <table class="table table-striped table-hover align-middle mb-0 text-nowrap">
          <thead class="table-dark">
            <tr>
              <th style="width: 50px;">ID</th>
              <th>Device Induk (VLAN)</th>
              <th>No / Kode Port</th>
              <th>Nama Deskripsi</th>
              <th>Status Koneksi</th>
              <th class="text-center" style="width: 100px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($ports)): ?>
                <tr>
                  <td colspan="6" class="text-center text-muted py-4" style="white-space: normal;">
                    <i class="bi bi-ethernet display-6 d-block mb-2 text-secondary"></i>
                    Belum ada data konfigurasi network port terdaftar.
                  </td>
                </tr>
            <?php else: ?>
                <?php foreach($ports as $port): ?>
                <tr>
                  <td><?= $port['id'] ?></td>
                  <td>
                    <span class="badge bg-light text-dark border px-2 py-1">
                      <i class="bi bi-router me-1 text-primary"></i> Device ID: <?= htmlspecialchars($port['network_device_id'] ?? '-') ?> 
                      (<?= htmlspecialchars($port['vlan'] ?? 'Tanpa VLAN') ?>)
                    </span>
                  </td>
                  <td><strong><code class="text-dark"><?= htmlspecialchars($port['port'] ?? '-') ?></code></strong></td>
                  <td><?= htmlspecialchars($port['nama'] ?? '-') ?></td>
                  <td>
                    <?php if(($port['status']) == 1): ?>
                        <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i> 1 - Up / Active</span>
                    <?php else: ?>
                        <span class="badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i> 0 - Down / Disable</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <button class="btn btn-sm btn-warning me-1" 
                            data-bs-toggle="modal" 
                            data-bs-target="#editPortModal"
                            data-id="<?= $port['id'] ?>"
                            data-device="<?= $port['network_device_id'] ?>"
                            data-port="<?= htmlspecialchars($port['port'] ?? '') ?>"
                            data-nama="<?= htmlspecialchars($port['nama'] ?? '') ?>"
                            data-status="<?= $port['status'] ?>"
                            title="Ubah Data Port">
                      <i class="bi bi-pencil-square"></i>
                    </button>
                    <a href="network_port.php?delete=<?= $port['id'] ?>" 
                       class="btn btn-sm btn-danger" 
                       onclick="return confirm('Apakah Anda yakin ingin menghapus konfigurasi port ini?')"
                       title="Hapus Data Port">
                      <i class="bi bi-trash"></i>
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</div>

<!-- ========================================== -->
<!-- MODAL COMPONENT UNTUK FORM CRUD (BOOTSTRAP)-->
<!-- ========================================== -->

<!-- MODAL TAMBAH PORT (CREATE) -->
<div class="modal fade" id="addPortModal" tabindex="-1" aria-labelledby="addPortModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <form action="network_port.php" method="POST" class="modal-content">
      <input type="hidden" name="action" value="create">
      <div class="modal-header">
        <h5 class="modal-title" id="addPortModalLabel"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Data Port Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Dropdown Pilih Network Device Induk -->
        <div class="mb-3">
            <label class="form-label fw-bold">Pilih Perangkat Jaringan (Device) <span class="text-danger">*</span></label>
            <select name="network_device_id" class="form-select" required>
                <option value="">-- Pilih Network Device --</option>
                <?php if(!empty($listDevices)): ?>
                    <?php foreach($listDevices as $dev): ?>
                        <option value="<?= $dev['id'] ?>">Device ID: <?= $dev['id'] ?> (VLAN: <?= htmlspecialchars($dev['vlan'] ?? '-') ?>)</option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="" disabled class="text-danger">Tidak ada data device! Tambah di menu Network Device dulu.</option>
                <?php endif; ?>
            </select>
        </div>
        <!-- Input Port -->
        <div class="mb-3">
          <label class="form-label fw-bold">No / Kode Port <span class="text-danger">*</span></label>
          <input type="text" name="port" class="form-control" placeholder="Contoh: Gi1/0/1, Eth1, Port-24" required>
        </div>
        <!-- Input Nama Deskripsi -->
        <div class="mb-3">
          <label class="form-label fw-bold">Nama Deskripsi / Keterangan <span class="text-danger">*</span></label>
          <input type="text" name="nama" class="form-control" placeholder="Contoh: Uplink Server Utama, Jalur PC Admin" required>
        </div>
        <!-- Dropdown Status (TINYINT 1 / 0) -->
        <div class="mb-3">
          <label class="form-label fw-bold">Status Port <span class="text-danger">*</span></label>
          <select name="status" class="form-select" required>
            <option value="1">1 (Up / Active)</option>
            <option value="0">0 (Down / Disable)</option>
          </select>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Port</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL EDIT PORT (UPDATE) - DENGAN SCROLLABLE -->
<div class="modal fade" id="editPortModal" tabindex="-1" aria-labelledby="editPortModalLabel" aria-hidden="true">
  <!-- TAMBAHKAN CLASS modal-dialog-scrollable AGAR RAPI DI LAYAR -->
  <div class="modal-dialog modal-dialog-scrollable">
    <form action="network_port.php" method="POST" class="modal-content">
      <!-- Hidden Input untuk memicu Logika 'update' di PHP Backend -->
      <input type="hidden" name="action" value="update">
      <!-- Hidden Input untuk menampung ID Primary Key port yang sedang diedit -->
      <input type="hidden" name="id" id="edit_port_id">
      
      <div class="modal-header">
        <h5 class="modal-title" id="editPortModalLabel"><i class="bi bi-pencil-square me-2 text-warning"></i>Ubah Konfigurasi Port</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        <!-- Dropdown Pilih Network Device Induk -->
        <div class="mb-3">
            <label class="form-label fw-bold">Perangkat Jaringan (Device) <span class="text-danger">*</span></label>
            <select name="network_device_id" id="edit_network_device_id" class="form-select" required>
                <?php foreach($listDevices as $dev): ?>
                    <option value="<?= $dev['id'] ?>">Device ID: <?= $dev['id'] ?> (VLAN: <?= htmlspecialchars($dev['vlan'] ?? '-') ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Input Port -->
        <div class="mb-3">
          <label class="form-label fw-bold">No / Kode Port <span class="text-danger">*</span></label>
          <input type="text" name="port" id="edit_port" class="form-control" required>
        </div>
        
        <!-- Input Nama Deskripsi -->
        <div class="mb-3">
          <label class="form-label fw-bold">Nama Deskripsi / Keterangan <span class="text-danger">*</span></label>
          <input type="text" name="nama" id="edit_nama" class="form-control" required>
        </div>
        
        <!-- Dropdown Status (TINYINT 1 / 0) -->
        <div class="mb-3">
          <label class="form-label fw-bold">Status Port <span class="text-danger">*</span></label>
          <select name="status" id="edit_status" class="form-select" required>
            <option value="1">1 (Up / Active)</option>
            <option value="0">0 (Down / Disable)</option>
          </select>
        </div>
      </div>
      
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<!-- SCRIPT JS UNTUK BINDING DATA TABEL KE MODAL EDIT PORT -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editPortModal = document.getElementById('editPortModal');
    if (editPortModal) {
        editPortModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            
            // Mengambil nilai atribut 'data-*' dari tombol edit yang diklik
            const id = button.getAttribute('data-id');
            const device = button.getAttribute('data-device');
            const port = button.getAttribute('data-port');
            const nama = button.getAttribute('data-nama');
            const status = button.getAttribute('data-status');
            
            // Menyisipkan nilai ke dalam field form modal edit secara otomatis
            document.getElementById('edit_port_id').value = id;
            document.getElementById('edit_network_device_id').value = device;
            document.getElementById('edit_port').value = port;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_status').value = status;
        });
    }
});
</script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
