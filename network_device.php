<?php
require_once __DIR__ . '/auth.php';
require_login();

// 1. Konfigurasi Koneksi Database
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

// Konfigurasi Pengaturan Pagination Tabel
$perPage = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ===================================================
    // LOGIKA PROSES AKSI FORM CRUD (POST & GET)
    // ===================================================

    // A. PROSES TAMBAH DATA DEVICE (Create)
    if (isset($_POST['action']) && $_POST['action'] == 'create') {
        $asset_id        = $_POST['asset_id'];
        $vlan            = $_POST['vlan'];
        $gateway         = $_POST['gateway'];
        $dns             = $_POST['dns'];
        $management_port = $_POST['management_port'];

        $stmtInsert = $conn->prepare("INSERT INTO network_devices (asset_id, vlan, gateway, dns, management_port) VALUES (?, ?, ?, ?, ?)");
        $stmtInsert->execute([$asset_id, $vlan, $gateway, $dns, $management_port]);
        
        header("Location: network_device.php?status=success_add");
        exit;
    }

    // B. PROSES UBAH DATA DEVICE (Update)
    if (isset($_POST['action']) && $_POST['action'] == 'update') {
        $id              = $_POST['id'];
        $asset_id        = $_POST['asset_id'];
        $vlan            = $_POST['vlan'];
        $gateway         = $_POST['gateway'];
        $dns             = $_POST['dns'];
        $management_port = $_POST['management_port'];

        $stmtUpdate = $conn->prepare("UPDATE network_devices SET asset_id = ?, vlan = ?, gateway = ?, dns = ?, management_port = ? WHERE id = ?");
        $stmtUpdate->execute([$asset_id, $vlan, $gateway, $dns, $management_port, $id]);
        
        header("Location: network_device.php?status=success_update");
        exit;
    }

    // C. PROSES HAPUS DATA DEVICE (Delete)
    if (isset($_GET['delete'])) {
        $idDelete = $_GET['delete'];

        $stmtDelete = $conn->prepare("DELETE FROM network_devices WHERE id = ?");
        $stmtDelete->execute([$idDelete]);
        
        header("Location: network_device.php?status=success_delete");
        exit;
    }

    // ===================================================
    // LOGIKA PENGAMBILAN DATA UNTUK DITAMPILKAN (Read)
    // ===================================================

    // 1. Mengambil referensi ID dari tabel assets untuk pilihan Dropdown
    $stmtAssets = $conn->prepare("SELECT id FROM assets ORDER BY id ASC");
    $stmtAssets->execute();
    $listAssets = $stmtAssets->fetchAll(PDO::FETCH_ASSOC);

    // 2. Menghitung total data network_devices untuk keperluan batasan Pagination
    $stmtCount = $conn->prepare("SELECT COUNT(*) FROM network_devices");
    $stmtCount->execute();
    $totalRows = $stmtCount->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);

    // 3. Mengambil record data dari tabel network_devices sesuai halaman aktif
    $stmtDevices = $conn->prepare("
        SELECT * FROM network_devices 
        ORDER BY id DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmtDevices->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmtDevices->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtDevices->execute();
    $devices = $stmtDevices->fetchAll(PDO::FETCH_ASSOC);

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
          <a href="network_device.php" class="nav-link active bg-primary text-white p-2 rounded"><i class="bi bi-router me-2"></i> Network Device</a>
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
            <a href="network_device.php" class="nav-link active bg-primary text-white p-2 rounded"><i class="bi bi-router me-2"></i> Network Device</a>
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
    <!-- PERBAIKAN: Menambahkan kelas 'col-12' agar di layar mobile mengambil porsi penuh 100%, dan mengatur ulang margin start 'ms-md-auto' -->
    <main class="col-12 col-md-8 col-lg-9 ms-sm-auto ms-md-auto px-md-4 pt-4 offset-md-4 offset-lg-3">

      <!-- Header Konten Utama -->
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 fs-4 fs-md-2">Dashboard Sistem - Network Devices</h1> <!-- PERBAIKAN: Responsif font size agar judul tidak patah ekstrem di HP -->
        <!-- Tombol Menu Khusus Tampilan Mobile -->
        <button class="btn d-md-none text-dark p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
          <i class="bi bi-list fs-2"></i>
        </button>
      </div>

      <!-- Notifikasi Flash Status CRUD -->
      <?php if(isset($_GET['status'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
              if($_GET['status'] == 'success_add') echo '<i class="bi bi-check-circle-fill me-2"></i> Perangkat jaringan baru berhasil ditambahkan!';
              if($_GET['status'] == 'success_update') echo '<i class="bi bi-check-circle-fill me-2"></i> Konfigurasi perangkat berhasil diperbarui!';
              if($_GET['status'] == 'success_delete') echo '<i class="bi bi-trash-fill me-2"></i> Perangkat jaringan berhasil dihapus!';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <!-- Tombol Pemicu Modal Tambah (C - Create) -->
      <div class="mb-3">
        <button class="btn btn-primary btn-sm btn-md-md" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
          <i class="bi bi-plus-circle me-1"></i> Tambah Network Device
        </button>
      </div>

      <!-- Tabel Data Network Devices (R - Read) -->
      <!-- PERBAIKAN: Memastikan w-100 (width 100%) dan overflow-x-auto berjalan aktif -->
      <div class="table-responsive w-100 bg-white p-2 p-md-3 rounded shadow-sm border" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <!-- PERBAIKAN: Menambahkan kelas 'text-nowrap' agar susunan kolom terkunci lurus horizontal dan memicu scrollbar saat menyempit -->
        <table class="table table-striped table-hover align-middle mb-0 text-nowrap">
          <thead class="table-dark">
            <tr>
              <th style="width: 50px;">ID</th>
              <th>Asset ID</th>
              <th>VLAN</th>
              <th>Gateway</th>
              <th>DNS</th>
              <th>Management Port</th>
              <th class="text-center" style="width: 100px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($devices)): ?>
                <tr>
                  <!-- PERBAIKAN: Menghapus pembatasan text-wrap khusus pesan kosong agar teksnya tidak melar memaksakan lebar tabel -->
                  <td colspan="7" class="text-center text-muted py-4" style="white-space: normal;">
                    <i class="bi bi-hdd-network display-6 d-block mb-2 text-secondary"></i>
                    Belum ada data konfigurasi network device terdaftar.
                  </td>
                </tr>
            <?php else: ?>
                <?php foreach($devices as $device): ?>
                <tr>
                  <td><?= $device['id'] ?></td>
                  <td>
                    <span class="badge bg-light text-dark border px-2 py-1">
                      <i class="bi bi-box-seam me-1 text-primary"></i> ID: <?= htmlspecialchars($device['asset_id'] ?? '-') ?>
                    </span>
                  </td>
                  <td><strong><?= htmlspecialchars($device['vlan'] ?? '-') ?></strong></td>
                  <td><code class="text-dark"><?= htmlspecialchars($device['gateway'] ?? '-') ?></code></td>
                  <td><?= htmlspecialchars($device['dns'] ?? '-') ?></td>
                  <td>
                    <span class="badge bg-secondary px-2 py-1">
                      <i class="bi bi-plug-fill me-1"></i> <?= htmlspecialchars($device['management_port'] ?? '-') ?>
                    </span>
                  </td>
                  <td class="text-center">
                    <!-- Tombol Aksi Edit -->
                    <button class="btn btn-sm btn-warning me-1" 
                            data-bs-toggle="modal" 
                            data-bs-target="#editDeviceModal"
                            data-id="<?= $device['id'] ?>"
                            data-asset="<?= $device['asset_id'] ?>"
                            data-vlan="<?= htmlspecialchars($device['vlan'] ?? '') ?>"
                            data-gateway="<?= htmlspecialchars($device['gateway'] ?? '') ?>"
                            data-dns="<?= htmlspecialchars($device['dns'] ?? '') ?>"
                            data-port="<?= htmlspecialchars($device['management_port'] ?? '') ?>"
                            title="Ubah Data">
                      <i class="bi bi-pencil-square"></i>
                    </button>
                    <!-- Tombol Aksi Hapus -->
                    <a href="network_device.php?delete=<?= $device['id'] ?>" 
                       class="btn btn-sm btn-danger" 
                       onclick="return confirm('Apakah Anda yakin ingin menghapus konfigurasi perangkat jaringan ini?')"
                       title="Hapus Data">
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

<!-- ========================================== -->
<!-- MODAL COMPONENT UNTUK FORM CRUD (BOOTSTRAP)-->
<!-- ========================================== -->

<!-- MODAL TAMBAH DEVICE (CREATE) - DENGAN SCROLLABLE -->
<div class="modal fade" id="addDeviceModal" tabindex="-1" aria-labelledby="addDeviceModalLabel" aria-hidden="true">
  <!-- TAMBAHKAN CLASS modal-dialog-scrollable DI BAWAH INI -->
  <div class="modal-dialog modal-dialog-scrollable">
    <form action="network_device.php" method="POST" class="modal-content">
      <!-- Hidden Input untuk memicu Logika 'create' di PHP Backend -->
      <input type="hidden" name="action" value="create">
      
      <div class="modal-header">
        <h5 class="modal-title" id="addDeviceModalLabel"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Perangkat Jaringan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        <!-- Dropdown Pilihan Asset ID -->
        <div class="mb-3">
            <label class="form-label fw-bold">Pilih Aset Utama <span class="text-danger">*</span></label>
            <select name="asset_id" class="form-select" required>
                <option value="">-- Pilih ID Asset Terdaftar --</option>
                <?php if(!empty($listAssets)): ?>
                    <?php foreach($listAssets as $ast): ?>
                        <option value="<?= $ast['id'] ?>">Asset ID: <?= $ast['id'] ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="" disabled class="text-danger">Tidak ada asset tersedia! Isi data di menu Assets dulu.</option>
                <?php endif; ?>
            </select>
            <div class="form-text text-muted small">Perangkat jaringan wajib dikaitkan dengan ID Aset yang valid.</div>
        </div>
        
        <!-- Input VLAN -->
        <div class="mb-3">
          <label class="form-label fw-bold">VLAN <span class="text-danger">*</span></label>
          <input type="text" name="vlan" class="form-control" placeholder="Contoh: 10 atau VLAN-MGMT" required>
        </div>
        
        <!-- Input Gateway -->
        <div class="mb-3">
          <label class="form-label fw-bold">Gateway <span class="text-danger">*</span></label>
          <input type="text" name="gateway" class="form-control" placeholder="Contoh: 10.10.6.1" required>
        </div>
        
        <!-- Input DNS -->
        <div class="mb-3">
          <label class="form-label fw-bold">DNS</label>
          <input type="text" name="dns" class="form-control" placeholder="Contoh: 8.8.8.8, 1.1.1.1">
        </div>
        
        <!-- Input Management Port -->
        <div class="mb-3">
          <label class="form-label fw-bold">Management Port</label>
          <input type="text" name="management_port" class="form-control" placeholder="Contoh: Fa0/24 atau Gi1/1">
        </div>
      </div>
      
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Device</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL EDIT DEVICE (UPDATE) -->
<div class="modal fade" id="editDeviceModal" tabindex="-1" aria-labelledby="editDeviceModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="network_device.php" method="POST" class="modal-content">
      <!-- Hidden Input untuk memicu Logika 'update' di PHP Backend -->
      <input type="hidden" name="action" value="update">
      <!-- Hidden Input untuk menampung ID Primary Key device yang sedang diedit -->
      <input type="hidden" name="id" id="edit_device_id">
      
      <div class="modal-header">
        <h5 class="modal-title" id="editDeviceModalLabel"><i class="bi bi-pencil-square me-2 text-warning"></i>Ubah Perangkat Jaringan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        <!-- Dropdown Pilihan Asset ID -->
        <div class="mb-3">
            <label class="form-label fw-bold">Pilih Aset Utama <span class="text-danger">*</span></label>
            <select name="asset_id" id="edit_asset_id" class="form-select" required>
                <?php foreach($listAssets as $ast): ?>
                    <option value="<?= $ast['id'] ?>">Asset ID: <?= $ast['id'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Input VLAN -->
        <div class="mb-3">
          <label class="form-label fw-bold">VLAN <span class="text-danger">*</span></label>
          <input type="text" name="vlan" id="edit_vlan" class="form-control" required>
        </div>
        
        <!-- Input Gateway -->
        <div class="mb-3">
          <label class="form-label fw-bold">Gateway <span class="text-danger">*</span></label>
          <input type="text" name="gateway" id="edit_gateway" class="form-control" required>
        </div>
        
        <!-- Input DNS -->
        <div class="mb-3">
          <label class="form-label fw-bold">DNS</label>
          <input type="text" name="dns" id="edit_dns" class="form-control">
        </div>
        
        <!-- Input Management Port -->
        <div class="mb-3">
          <label class="form-label fw-bold">Management Port</label>
          <input type="text" name="management_port" id="edit_management_port" class="form-control">
        </div>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<!-- SCRIPT JS UNTUK BINDING DATA TABEL KE MODAL EDIT -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editDeviceModal = document.getElementById('editDeviceModal');
    if (editDeviceModal) {
        editDeviceModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            
            // Mengambil nilai atribut 'data-*' dari tombol edit yang diklik
            const id = button.getAttribute('data-id');
            const asset = button.getAttribute('data-asset');
            const vlan = button.getAttribute('data-vlan');
            const gateway = button.getAttribute('data-gateway');
            const dns = button.getAttribute('data-dns');
            const port = button.getAttribute('data-port');
            
            // Menyisipkan nilai ke dalam field form modal edit secara otomatis
            document.getElementById('edit_device_id').value = id;
            document.getElementById('edit_asset_id').value = asset;
            document.getElementById('edit_vlan').value = vlan;
            document.getElementById('edit_gateway').value = gateway;
            document.getElementById('edit_dns').value = dns;
            document.getElementById('edit_management_port').value = port;
        });
    }
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>