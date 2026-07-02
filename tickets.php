<?php
require_once __DIR__ . '/auth.php';
require_login();

// Konfigurasi Database sesuai dashboard Anda
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

// Pagination
$perPage = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ambil data untuk dropdown Form (Tambah/Edit)
    $rooms = $conn->query("SELECT id, nama FROM rooms")->fetchAll(PDO::FETCH_ASSOC);
    $users = $conn->query("SELECT id, username FROM users")->fetchAll(PDO::FETCH_ASSOC);

    // Ambil total data untuk pagination
    $stmtCount = $conn->query("SELECT COUNT(*) FROM tickets");
    $totalTickets = $stmtCount->fetchColumn();
    $totalPages = ceil($totalTickets / $perPage);

    // PERBAIKAN UTAMA: Menggunakan LEFT JOIN ke tabel users untuk mengambil kolom nama asli pelapor
    $query = "SELECT t.*, u.nama AS nama_pelapor 
              FROM tickets t 
              LEFT JOIN users u ON t.pelapor = u.id 
              ORDER BY t.created_at DESC 
              LIMIT :limit OFFSET :offset";
              
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
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
          <a href="tickets.php" class="nav-link active bg-primary <?= ($currentPage == 'tickets.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
            <i class="bi bi-ticket-perforated-fill me-2"></i> Tikets
          </a>
        </li>
        <!-- MAINTENANCE -->
        <li class="nav-item">
        <a href="maintenance.php" class="nav-link text-white p-2 rounded">
            <i class="bi bi-wrench-adjustable-circle me-2"></i> Maintenance
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
          <a href="tickets.php" class="nav-link active bg-primary <?= ($currentPage == 'tickets.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
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
    </nav> <!-- Penutup elemen sidebar internal -->

    <!-- AREA UTAMA KONTEN (Gunakan pembungkus ini agar susunan halaman tidak bergeser tertimpa sidebar) -->
    <main class="col-md-8 ms-sm-auto col-lg-9 px-md-4 pt-4 offset-md-4 offset-lg-3">

      <!-- KONTEN UTAMA (MAIN CONTENT) -->
      <main class="px-md-4 py-4 bg-light text-dark" style="min-height: 100vh;">
        
        <!-- Header Konten -->
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom border-secondary-subtle">
          <h1 class="h2 fw-bold text-dark m-0">Manajemen Tiket</h1>
          <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-sm btn-primary px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
              <i class="bi bi-plus-lg me-2"></i>Buat Tiket Baru
            </button>
          </div>
        </div>

  <!-- Row Kartu Statistik -->
  <div class="row g-3 mb-4">
    <!-- Kartu Total Tiket -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card bg-white text-dark h-100 border border-secondary-subtle shadow-sm">
        <div class="card-body d-flex align-items-center justify-content-between p-3">
          <div>
            <h6 class="card-title text-uppercase text-muted small fw-bold mb-1" style="letter-spacing: 0.5px;">Total Tiket</h6>
            <span class="h3 font-weight-bold m-0 text-primary"><?= count($tickets); ?></span>
          </div>
          <div class="fs-1 text-primary opacity-50"><i class="bi bi-ticket-perforated"></i></div>
        </div>
      </div>
    </div>
    <!-- Kartu Tiket Baru (Open) -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card bg-white text-dark h-100 border border-success-subtle shadow-sm" style="border-left: 5px solid #198754 !important;">
        <div class="card-body d-flex align-items-center justify-content-between p-3">
          <div>
            <h6 class="card-title text-uppercase text-muted small fw-bold mb-1" style="letter-spacing: 0.5px;">Tiket Baru (Open)</h6>
            <span class="h3 font-weight-bold m-0 text-success">
              <?= count(array_filter($tickets, function($t) { return $t['status'] == 1; })); ?>
            </span>
          </div>
          <div class="fs-1 text-success opacity-50"><i class="bi bi-envelope-open"></i></div>
        </div>
      </div>
    </div>
    <!-- Kartu Diproses -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card bg-white text-dark h-100 border border-info-subtle shadow-sm" style="border-left: 5px solid #0dcaf0 !important;">
        <div class="card-body d-flex align-items-center justify-content-between p-3">
          <div>
            <h6 class="card-title text-uppercase text-muted small fw-bold mb-1" style="letter-spacing: 0.5px;">Diproses</h6>
            <span class="h3 font-weight-bold m-0 text-info">
              <?= count(array_filter($tickets, function($t) { return $t['status'] == 2; })); ?>
            </span>
          </div>
          <div class="fs-1 text-info opacity-50"><i class="bi bi-gear-wide-connected"></i></div>
        </div>
      </div>
    </div>
    <!-- Kartu Selesai (Closed) -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card bg-white text-dark h-100 border border-danger-subtle shadow-sm" style="border-left: 5px solid #dc3545 !important;">
        <div class="card-body d-flex align-items-center justify-content-between p-3">
          <div>
            <h6 class="card-title text-uppercase text-muted small fw-bold mb-1" style="letter-spacing: 0.5px;">Selesai (Closed)</h6>
            <span class="h3 font-weight-bold m-0 text-danger">
              <?= count(array_filter($tickets, function($t) { return $t['status'] == 3; })); ?>
            </span>
          </div>
          <div class="fs-1 text-danger opacity-50"><i class="bi bi-check-circle"></i></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Area Input Pencarian Live -->
  <div class="mb-3 col-md-4">
    <div class="input-group shadow-sm">
      <span class="input-group-text bg-white border-secondary-subtle text-muted"><i class="bi bi-search"></i></span>
      <input type="text" id="searchTicket" class="form-control bg-white border-secondary-subtle text-dark" placeholder="Cari nomor atau judul tiket...">
    </div>
  </div>

<!-- Area Tabel Antrean Tiket (Versi Putih Bersih + Sangat Responsif Mobile) -->
  <div class="card bg-white text-dark border border-secondary-subtle shadow-sm mb-4">
    <div class="card-header bg-light border-bottom border-secondary-subtle d-flex justify-content-between align-items-center py-3">
      <h6 class="m-0 font-weight-bold text-dark fw-bold"><i class="bi bi-table me-2 text-secondary"></i>Daftar Antrean Tiket</h6>
    </div>
    
    <!-- PERBAIKAN UTAMA: Memastikan pembungkus table-responsive terpasang sempurna -->
    <div class="card-body p-0 table-responsive">
      <!-- PERBAIKAN: Menambahkan class 'table-bordered' untuk memberikan garis pembatas penuh antar kolom dan baris -->
      <table class="table table-hover table-striped table-bordered align-middle m-0" style="min-width: 850px;">
        <thead class="table-light text-nowrap">
          <tr class="text-secondary border-bottom border-secondary-subtle">
            <th class="ps-4 py-3" style="width: 100px;">Nomor</th>
            <th class="py-3" style="min-width: 180px;">Judul Kendala</th>
            <th class="py-3">Ruangan</th>
            <th class="py-3">Prioritas</th>
            <th class="py-3">Status</th>
            <th class="py-3">Pelapor</th>
            <th class="py-3">Teknisi</th>
            <th class="text-center pe-4 py-3" style="width: 180px;">Aksi</th>
          </tr>
        </thead>
        <tbody class="text-nowrap">
          <?php if (count($tickets) > 0): ?>
            <?php foreach ($tickets as $row): ?>
              <tr>
                <td class="ps-4 fw-bold text-primary"><?= htmlspecialchars($row['nomor']); ?></td>
                <td class="fw-semibold text-dark text-wrap" style="max-width: 250px;"><?= htmlspecialchars($row['judul']); ?></td>
                <td>
                  <!-- PERBAIKAN: Mengubah warna badge Ruangan agar kontras tinggi dengan teks putih tegas -->
                  <span class="badge bg-secondary text-white fw-bold px-2 py-1">ID: <?= htmlspecialchars($row['room_id'] ?? '-'); ?></span>
                </td>
                <td>
                  <?php 
                    // PERBAIKAN: Menggunakan warna padat kontras teks hitam untuk tingkat Medium
                    if ($row['prioritas'] == 1) echo '<span class="badge bg-light text-dark border border-secondary-subtle px-2 py-1">Low</span>';
                    elseif ($row['prioritas'] == 2) echo '<span class="badge bg-warning text-dark fw-bold px-2 py-1">Medium</span>';
                    else echo '<span class="badge bg-danger text-white fw-bold px-2 py-1">High</span>';
                  ?>
                </td>
                <td>
                  <?php 
                    // PERBAIKAN: Menggunakan teks hitam (text-dark) pada status In Progress agar tajam di layar putih
                    if ($row['status'] == 1) echo '<span class="badge bg-success text-white fw-bold px-2 py-1">Open</span>';
                    elseif ($row['status'] == 2) echo '<span class="badge bg-info text-dark fw-bold px-2 py-1">In Progress</span>';
                    else echo '<span class="badge bg-secondary text-white fw-bold px-2 py-1">Closed</span>';
                  ?>
                </td>
                <!-- PERUBAHAN: Menampilkan Nama Pelapor dengan style teks yang lebih jelas -->
                <td class="text-dark fw-medium"><?= htmlspecialchars($row['nama_pelapor'] ?? 'Tidak Diketahui'); ?></td>
                <td><span class="text-dark fw-medium"><?= htmlspecialchars($row['teknisi'] ?? 'Belum Ditunjuk'); ?></span></td>
                <!-- PERUBAHAN: Tombol Komen diarahkan ke file ticket_comments.php menggunakan tag anchor <a> -->
                <td class="text-center pe-4">
                  <div class="d-flex justify-content-center gap-2">
                    <button class="btn btn-sm btn-outline-primary px-2 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id']; ?>" title="Edit Status/Teknisi">
                      <i class="bi bi-pencil-square me-1"></i> Detail
                    </button>
                    <a href="ticket_comments.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-info px-2 fw-bold shadow-sm text-dark" title="Lihat/Tambah Komentar">
                      <i class="bi bi-chat-left-dots me-1"></i> Komen
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary-subtle"></i> Belum ada data tiket masuk.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<!-- MODAL TAMBAH TIKET (BG PUTIH & UKURAN LEBAR) -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content bg-white text-dark border-0 shadow-lg">

      <!-- Modal Header -->
      <div class="modal-header border-bottom border-light bg-light">
        <h5 class="modal-title d-flex align-items-center fw-bold text-primary" id="modalTambahLabel">
          <i class="bi bi-ticket-perforated-fill me-2"></i> Buat Tiket Aduan Baru
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <form action="proses_ticket.php?action=create" method="POST">
        <div class="modal-body p-4">
          <div class="row g-4">
            
            <!-- KOLOM KIRI (Judul & Deskripsi Masalah) -->
            <div class="col-md-7 border-end border-light">
              <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Judul Kendala / Keluhan</label>
                <input type="text" name="judul" class="form-control border-secondary-subtle text-dark py-2" placeholder="Contoh: Printer Ruang Staff Macet" required>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Deskripsi Detail Masalah</label>
                <textarea name="deskripsi" class="form-control border-secondary-subtle text-dark" rows="8" placeholder="Jelaskan secara rinci kronologi masalah atau detail kendala teknis..." required></textarea>
              </div>
            </div>

            <!-- KOLOM KANAN (Metadata, Lokasi & Prioritas) -->
            <div class="col-md-5">
              <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Nomor Tiket (Sistem)</label>
                <input type="text" name="nomor" class="form-control bg-light border-0 text-primary fw-bold py-2" value="TKT-<?= date('YmdHis'); ?>" readonly>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Lokasi Ruangan</label>
                <select name="room_id" class="form-select border-secondary-subtle text-dark py-2" required>
                  <option value="" disabled selected>-- Pilih Ruangan --</option>
                  <?php if (!empty($rooms)): ?>
                    <?php foreach($rooms as $r): ?>
                      <option value="<?= $r['id']; ?>">
                        <?= htmlspecialchars($r['nama'] ?? 'ID: ' . $r['id']); ?>
                      </option>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <option value="1">Ruang IT</option>
                    <option value="2">Ruang Administrasi</option>
                  <?php endif; ?>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Tingkat Prioritas</label>
                <select name="prioritas" class="form-select border-secondary-subtle text-dark py-2">
                  <option value="1">🟢 Low (Rendah)</option>
                  <option value="2" selected>🟡 Medium (Sedang)</option>
                  <option value="3">🔴 High (Mendesak / Kritis)</option>
                </select>
              </div>

              <!-- Petunjuk Pengisian -->
              <div class="card bg-light border-0 p-3 mt-4 text-muted small">
                <div class="d-flex align-items-start">
                  <i class="bi bi-info-circle-fill text-info me-2 mt-1"></i>
                  <div>
                    <span class="text-dark d-block mb-1 fw-bold">Petunjuk Pengisian</span>
                    Pastikan informasi ruangan dan deskripsi masalah diisi sejelas mungkin agar tim teknisi kami dapat merespons aduan dengan cepat dan tepat.
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer border-top border-light bg-light d-flex justify-content-end p-3">
          <button type="button" class="btn btn-sm btn-secondary me-2" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-success px-4 py-2 fw-bold">
            <i class="bi bi-send-fill me-1"></i> Kirim Tiket
          </button>
        </div>
      </form>

    </div>
  </div>
</div>

<!-- MODAL EDIT TIKET (BG PUTIH & UKURAN LEBAR) -->
<div class="modal fade" id="modalEdit<?= $row['id']; ?>" tabindex="-1" aria-labelledby="modalEditLabel<?= $row['id']; ?>" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content bg-white text-dark border-0 shadow-lg">
      
      <!-- Modal Header -->
      <div class="modal-header border-bottom border-light bg-light">
        <h5 class="modal-title d-flex align-items-center fw-bold text-dark" id="modalEditLabel<?= $row['id']; ?>">
          <i class="bi bi-pencil-square me-2 text-warning"></i> Perbarui Tiket #<?= htmlspecialchars($row['nomor']); ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <form action="proses_ticket.php?action=update" method="POST">
        <div class="modal-body p-4">
          <div class="row g-4">
            
            <input type="hidden" name="id" value="<?= $row['id']; ?>">

            <!-- KOLOM KIRI (Judul & Riwayat Keluhan) -->
            <div class="col-md-7 border-end border-light">
              <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Judul Kendala / Keluhan</label>
                <input type="text" name="judul" class="form-control border-secondary-subtle text-dark py-2" value="<?= htmlspecialchars($row['judul']); ?>" required>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Deskripsi Detail Masalah (Riwayat Awal)</label>
                <textarea name="deskripsi" class="form-control bg-light border-0 text-muted" rows="8" readonly><?= htmlspecialchars($row['deskripsi'] ?? 'Tidak ada deskripsi tambahan.'); ?></textarea>
              </div>
            </div>

            <!-- KOLOM KANAN (Status & Teknisi) -->
            <div class="col-md-5">
              <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Nomor Tiket Berjalan</label>
                <input type="text" class="form-control bg-light border-0 text-info fw-bold py-2" value="<?= htmlspecialchars($row['nomor']); ?>" readonly>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Status Penanganan</label>
                <select name="status" class="form-select border-secondary-subtle text-dark py-2" required>
                  <option value="1" <?= $row['status'] == 1 ? 'selected' : ''; ?>>🟢 Open (Belum Ditangani)</option>
                  <option value="2" <?= $row['status'] == 2 ? 'selected' : ''; ?>>🟡 In Progress (Sedang Dikerjakan)</option>
                  <option value="3" <?= $row['status'] == 3 ? 'selected' : ''; ?>>🔴 Closed (Selesai Diselesaikan)</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Petugas / Teknisi Lapangan</label>
                <select name="teknisi" class="form-select border-secondary-subtle text-dark py-2">
                  <option value="">-- Belum Ditunjuk / Kosong --</option>
                  <?php if (!empty($users)): ?>
                    <?php foreach($users as $u): ?>
                      <option value="<?= $u['id']; ?>" <?= $row['teknisi'] == $u['id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($u['username'] ?? $u['nama']); ?>
                      </option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>

              <!-- Info Box Metadata -->
              <div class="card bg-light border-0 p-3 mt-4 text-muted small">
                <div class="d-flex justify-content-between mb-1">
                  <span>Waktu Masuk Tiket:</span>
                  <span class="text-dark fw-bold"><?= date('d-m-Y H:i', strtotime($row['created_at'])); ?></span>
                </div>
                <div class="d-flex justify-content-between">
                  <span>ID Pelapor Asal:</span>
                  <span class="text-dark fw-bold">User ID: <?= htmlspecialchars($row['pelapor'] ?? '-'); ?></span>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer border-top border-light bg-light d-flex justify-content-end p-3">
          <button type="button" class="btn btn-sm btn-secondary me-2" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-warning px-4 py-2 fw-bold text-dark">
            <i class="bi bi-arrow-clockwise me-1"></i> Simpan Perubahan
          </button>
        </div>
      </form>

    </div>
  </div>
</div>

<!-- SCRIPT JS UNTUK PENCARIAN & NOTIFIKASI -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    
    // 1. FITUR PENCARIAN LIVE PADA TABEL TIKET
    // Buat input pencarian otomatis bekerja jika Anda menambahkan elemen input dengan id="searchTicket"
    const searchInput = document.getElementById("searchTicket");
    if (searchInput) {
        searchInput.addEventListener("keyup", function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll("table tbody tr");

            rows.forEach(row => {
                // Jangan sembunyikan baris jika tabel dalam keadaan kosong (kolom tidak ditemukan)
                if(row.cells.length > 1) {
                    const nomor = row.cells[0].textContent.toLowerCase();
                    const judul = row.cells[1].textContent.toLowerCase();
                    
                    if (nomor.includes(filter) || judul.includes(filter)) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                }
            });
        });
    }

    // 2. OTOMATIS HILANGKAN ALERT NOTIFIKASI (FADE OUT)
    // Jika ada alert sukses/gagal dari bootstrap, akan hilang sendiri setelah 4 detik
    const alerts = document.querySelectorAll(".alert");
    alerts.forEach(function (alert) {
        setTimeout(function () {
            // Menggunakan class bawaan Bootstrap 5 untuk transisi halus
            alert.classList.remove("show");
            alert.classList.add("fade");
            setTimeout(() => alert.remove(), 150);
        }, 4000);
    });

    // 3. RESET FORM MODAL TAMBAH SAAT DITUTUP
    // Memastikan form bersih kembali saat user membatalkan pengisian aduan
    const modalTambah = document.getElementById("modalTambah");
    if (modalTambah) {
        modalTambah.addEventListener("hidden.bs.modal", function () {
            const form = this.querySelector("form");
            if (form) {
                form.reset();
                // Tetap pertahankan nomor tiket otomatis agar tidak ikut hilang
                const nomorInput = form.querySelector("input[name='nomor']");
                if (nomorInput) {
                    const timestamp = new Date().toISOString().slice(0, 19).replace(/[-T:]/g, "");
                    nomorInput.value = "TKT-" + timestamp;
                }
            }
        });
    }
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
