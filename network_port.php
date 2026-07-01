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
        <!-- VENDORS (Mobile) -->
        <li class="nav-item">
          <a href="vendors.php" class="nav-link <?= ($currentPage == 'vendors.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
            <i class="bi bi-building me-2"></i> Vendors <!-- Tetap ikon gedung mitra bisnis -->
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
            <a href="server.php" class="nav-link  text-white p-2 rounded"><i class="bi bi-hdd-network me-2"></i> Server</a>
          </li>
          <li class="nav-item">
            <a href="network_device.php" class="nav-link text-white p-2 rounded"><i class="bi bi-router me-2"></i> Network Device</a>
          </li>
          <li class="nav-item">
            <a href="network_port.php" class="nav-link active bg-primary text-white p-2 rounded"><i class="bi bi-ethernet me-2"></i> Network Port</a>
          </li>
          <!-- VENDORS (Desktop) -->
          <li class="nav-item">
            <a href="vendors.php" class="nav-link <?= ($currentPage == 'vendors.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
              <i class="bi bi-building me-2"></i> Vendors
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
    <!-- PERBAIKAN: Menambahkan kelas 'col-12' agar mengambil lebar penuh di layar mobile, dan menyesuaikan responsivitas margin start 'ms-md-auto' -->
    <main class="col-12 col-md-8 col-lg-9 ms-sm-auto ms-md-auto px-md-4 pt-4 offset-md-4 offset-lg-3">

      <!-- Header Konten Utama -->
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 fs-4 fs-md-2">Dashboard Sistem - Network Ports</h1> <!-- PERBAIKAN: Ukuran font responsif agar judul tidak patah berantakan di HP -->
        <!-- Tombol Menu Khusus Tampilan Mobile -->
        <button class="btn d-md-none text-dark p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
          <i class="bi bi-list fs-2"></i>
        </button>
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
      <!-- PERBAIKAN: Memaksa w-100 (width 100%) dan mengunci fungsi overflow horizontal untuk scroll mobile -->
      <div class="table-responsive w-100 bg-white p-2 p-md-3 rounded shadow-sm border" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <!-- PERBAIKAN: Menambahkan kelas 'text-nowrap' agar teks data dan baris judul terkunci lurus secara horizontal -->
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
                  <!-- PERBAIKAN: Menghapus batasan text-nowrap khusus untuk teks pemberitahuan kosong agar tidak memanjang -->
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
                    <!-- Tombol Aksi Edit (Menyalurkan Data ke Atribut HTML) -->
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
                    <!-- Tombol Aksi Hapus (D - Delete) -->
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
