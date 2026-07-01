<?php
require_once __DIR__ . '/auth.php';
require_login();

// 1. Konfigurasi Koneksi Database
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $status_msg = '';

    // ========================================================
    // A. LOGIKA TAMBAH DATA (CREATE)
    // ========================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
        $kategori_id = !empty($_POST['kategori_id']) ? intval($_POST['kategori_id']) : null;
        $nama        = trim($_POST['nama']);
        $url         = trim($_POST['url']);
        $ip          = trim($_POST['ip']);
        $username_v  = trim($_POST['username']);
        $password_v  = $_POST['password']; 
        $tipe        = !empty($_POST['tipe']) ? intval($_POST['tipe']) : null;
        $catatan     = trim($_POST['catatan']);
        
        if (!empty($nama)) {
            $stmt = $conn->prepare("INSERT INTO password_vaults (kategori_id, nama, url, ip, username, password, tipe, catatan) 
                                    VALUES (:kategori_id, :nama, :url, :ip, :username, :password, :tipe, :catatan)");
            $stmt->execute([
                ':kategori_id' => $kategori_id,
                ':nama'        => $nama,
                ':url'         => $url,
                ':ip'          => $ip,
                ':username'    => $username_v,
                ':password'    => $password_v,
                ':tipe'        => $tipe,
                ':catatan'     => $catatan
            ]);
            
            header("Location: password_vault.php?status=success_add");
            exit;
        }
    }

    // ========================================================
    // B. LOGIKA UBAH DATA (UPDATE) DENGAN PENCATATAN RIWAYAT
    // ========================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
        $id            = intval($_POST['id']);
        $kategori_id   = !empty($_POST['kategori_id']) ? intval($_POST['kategori_id']) : null;
        $nama          = trim($_POST['nama']);
        $url           = trim($_POST['url']);
        $ip            = trim($_POST['ip']);
        $username_v    = trim($_POST['username']);
        $password_baru = $_POST['password']; 
        $tipe          = !empty($_POST['tipe']) ? intval($_POST['tipe']) : null;
        $catatan       = trim($_POST['catatan']);
        
        if ($id > 0 && !empty($nama)) {
            
            // Step 1: Ambil data password saat ini di database sebelum diperbarui
            $stmtCheck = $conn->prepare("SELECT password FROM password_vaults WHERE id = :id");
            $stmtCheck->execute([':id' => $id]);
            $current_data = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($current_data) {
                $password_lama = $current_data['password'];
                
                // Step 2: Cek apakah nilai password mengalami perubahan
                if ($password_lama !== $password_baru) {
                    // Simpan password lama ke tabel password_histories sesuai kolom database Anda
                    $stmtLog = $conn->prepare("INSERT INTO password_histories (vault_id, password_lama, diubah_pada) 
                                               VALUES (:vault_id, :password_lama, NOW())");
                    $stmtLog->execute([
                        ':vault_id'      => $id,
                        ':password_lama' => $password_lama
                    ]);
                }
            }

            // Step 3: Jalankan update data baru ke dalam tabel password_vaults
            $stmt = $conn->prepare("UPDATE password_vaults SET 
                                    kategori_id = :kategori_id, nama = :nama, url = :url, ip = :ip, 
                                    username = :username, password = :password, tipe = :tipe, catatan = :catatan 
                                    WHERE id = :id");
            $stmt->execute([
                ':kategori_id' => $kategori_id,
                ':nama'        => $nama,
                ':url'         => $url,
                ':ip'          => $ip,
                ':username'    => $username_v,
                ':password'    => $password_baru,
                ':tipe'        => $tipe,
                ':catatan'     => $catatan,
                ':id'          => $id
            ]);
            
            header("Location: password_vault.php?status=success_edit");
            exit;
        }
    }

    // ========================================================
    // C. LOGIKA HAPUS DATA (DELETE)
    // ========================================================
    if (isset($_GET['action']) && $_GET['action'] === 'delete') {
        $id = intval($_GET['id']);
        
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM password_vaults WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
            header("Location: password_vault.php?status=success_delete");
            exit;
        }
    }

    // ========================================================
    // D. LOGIKA MENGAMBIL DATA (READ) DENGAN LEFT JOIN
    // ========================================================
    $sqlSelect = "SELECT pv.*, pc.nama AS nama_kategori 
                  FROM password_vaults pv 
                  LEFT JOIN password_categories pc ON pv.kategori_id = pc.id 
                  ORDER BY pv.id DESC";
    $stmtSelect = $conn->query($sqlSelect);
    $vaults = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

    // Mengambil semua list kategori untuk kebutuhan opsi Dropdown di Modal Form (Tambah & Edit)
    $stmtCat = $conn->query("SELECT * FROM password_categories ORDER BY nama ASC");
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Kesalahan Database: " . $e->getMessage());
}

// Menangkap status operasi untuk pemicu komponen Alert/Notifikasi di HTML
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success_add') $status_msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>Data vault password berhasil disimpan!<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    if ($_GET['status'] === 'success_edit') $status_msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>Data vault password berhasil diperbarui!<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    if ($_GET['status'] === 'success_delete') $status_msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>Data vault password berhasil dihapus!<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
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
          <a href="password_vault.php" class="nav-link active bg-primary text-white p-2 rounded">
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
          <a href="password_vault.php" class="nav-link active bg-primary text-white p-2 rounded">
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

    <!-- ========================================== -->
    <!-- 3. MAIN CONTENT VAULT (FULL WIDTH & RAPI)  -->
    <!-- ========================================== -->
    <!-- FIX: Menggunakan flex-grow-1 agar konten melebar luas mengisi sisa ruang desktop -->
    <main class="col-12 col-md-8 col-lg-9 ms-md-auto px-2 px-md-4 py-4" style="background-color: #ffffff !important; min-height: 100vh; overflow-x: hidden;">
      
      <!-- Header Konten Utama -->
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 border-bottom border-light-subtle pb-3">
        <div>
          <h3 class="fw-bold mb-1 text-dark fs-4 fs-md-3">
            <i class="bi bi-safe text-primary me-2"></i> Password Vault
          </h3>
          <small class="text-secondary d-block">Menampilkan daftar data kredensial dan kata sandi aman sistem</small>
        </div>
        <!-- Badge Informasi Total Data Vault -->
        <span class="badge bg-primary px-3 py-2 fs-6">
          Total: <?= count($vaults); ?> Vault
        </span>
      </div>

      <!-- Menampilkan Alert Status CRUD Jika Ada -->
      <?php if (!empty($status_msg)) echo $status_msg; ?>

      <!-- Tombol Tambah Data -->
      <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalTambah">
          <i class="bi bi-plus-lg me-1"></i> Tambah Kredensial
      </button>

      <!-- Card Box untuk Tabel Data -->
      <div class="card bg-white text-dark border-light-subtle shadow-sm mb-4">
        <div class="card-header bg-light border-light-subtle d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
          <h5 class="card-title mb-0 fw-semibold text-primary fs-6 fs-md-5">
            <i class="bi bi-table me-2"></i> Data Kredensial Vault
          </h5>
          <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Mode CRUD Terintegrasi</span>
        </div>
        
        <div class="card-body p-0">
          <div class="table-responsive">
            <!-- FIX: Menghapus table-sm agar longgar, menambah table-bordered untuk garis pembatas, dan table-striped untuk warna baris selang-seling -->
            <table class="table table-striped table-bordered table-hover align-middle mb-0 text-nowrap" style="border-color: #dee2e6;">
              <thead class="table-light text-dark fw-bold border-bottom border-2">
                <tr>
                  <th scope="col" class="text-center p-3" style="width: 60px;">No</th>
                  <th scope="col" class="p-3" style="width: 150px;">Kategori</th>
                  <th scope="col" class="p-3">Nama Akun / Layanan</th>
                  <th scope="col" class="p-3">URL / Link</th>
                  <th scope="col" class="p-3">IP Address</th>
                  <th scope="col" class="p-3">Username</th>
                  <th scope="col" class="p-3">Password</th>
                  <th scope="col" class="p-3" style="width: 120px;">Tipe</th>
                  <th scope="col" class="p-3">Catatan</th>
                  <th scope="col" class="text-center p-3" style="width: 250px;">Aksi</th>
                </tr>
              </thead>
              <tbody class="text-dark">
                <?php if (empty($vaults)): ?>
                  <tr>
                    <td colspan="10" class="text-center py-5 text-secondary text-wrap">
                      <i class="bi bi-shield-slash fs-1 d-block mb-2 text-muted"></i>
                      Belum ada data kredensial yang tersedia di database.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php $no = 1; foreach ($vaults as $row): ?>
                    <tr>
                      <td class="text-center fw-semibold text-secondary p-3"><?= $no++; ?></td>
                      <td class="p-3">
                        <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle px-2 py-1 fs-7">
                          <?= htmlspecialchars($row['nama_kategori'] ?? 'Tanpa Kategori'); ?>
                        </span>
                      </td>
                      <td class="fw-semibold text-dark p-3"><?= htmlspecialchars($row['nama']); ?></td>
                      <td class="text-wrap p-3" style="max-width: 200px;">
                        <?php if(!empty($row['url'])): ?>
                          <a href="<?= htmlspecialchars($row['url']); ?>" target="_blank" class="text-decoration-none text-truncate d-inline-block" style="max-width: 100%;"><i class="bi bi-box-arrow-up-right me-1"></i> Buka Link</a>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                      <td class="p-3"><code><?= !empty($row['ip']) ? htmlspecialchars($row['ip']) : '-'; ?></code></td>
                      <td class="p-3"><?= htmlspecialchars($row['username']); ?></td>
                      <td class="p-3">
                        <div class="input-group input-group-sm" style="width: 150px;">
                          <input type="password" class="form-control bg-light border-0" value="<?= htmlspecialchars($row['password']); ?>" readonly id="passInput<?= $row['id']; ?>">
                          <button class="btn btn-outline-secondary border-0" type="button" onclick="togglePassword(<?= $row['id']; ?>)">
                            <i class="bi bi-eye" id="eyeIcon<?= $row['id']; ?>"></i>
                          </button>
                        </div>
                      </td>
                      <td class="p-3">
                        <?php 
                          if ($row['tipe'] == 1) echo '<span class="badge bg-secondary">Server</span>';
                          elseif ($row['tipe'] == 2) echo '<span class="badge bg-dark">Network Device</span>';
                          else echo '<span class="badge bg-light text-dark border">Lainnya</span>';
                        ?>
                      </td>
                      <td class="text-wrap text-muted fs-7 p-3" style="max-width: 150px;">
                        <?= !empty($row['catatan']) ? htmlspecialchars($row['catatan']) : '-'; ?>
                      </td>
                      <td class="text-center p-3">
                        <div class="d-inline-flex gap-1">
                          <button class="btn btn-warning btn-sm fw-medium" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id']; ?>">
                            <i class="bi bi-pencil-square"></i> Edit
                          </button>
                          <a href="password_vault.php?action=delete&id=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data kredensial ini?')">
                            <i class="bi bi-trash"></i> Hapus
                          </a>
                          <button class="btn btn-outline-secondary btn-sm fw-medium" data-bs-toggle="modal" data-bs-target="#modalRiwayat<?= $row['id']; ?>" onclick="loadHistory(<?= $row['id']; ?>)">
                            <i class="bi bi-clock-history"></i> Riwayat
                          </button>
                        </div>
                      </td>
                    </tr>

                    <!-- [MODAL EDIT DINAMIS ANDA TETAP BERADA DI SINI] -->

                    <!-- ========================================== -->
                    <!-- MODAL RIWAYAT POP-UP (DINAMIS PER BARIS)   -->
                    <!-- ========================================== -->
                    <div class="modal fade text-wrap" id="modalRiwayat<?= $row['id']; ?>" tabindex="-1" aria-labelledby="modalRiwayatLabel<?= $row['id']; ?>" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-white text-dark shadow">
                          <div class="modal-header bg-light border-bottom">
                            <h5 class="modal-title fw-bold text-secondary" id="modalRiwayatLabel<?= $row['id']; ?>">
                              <i class="bi bi-clock-history me-2"></i>Riwayat Perubahan Sandi
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body p-3">
                            <p class="small text-muted mb-3">Layanan: <strong class="text-dark"><?= htmlspecialchars($row['nama']); ?></strong></p>
                            <!-- Konten tabel riwayat diisi dinamis via JavaScript Fetch AJAX -->
                            <div id="contentRiwayat<?= $row['id']; ?>">
                              <div class="text-center py-3 text-muted">
                                <div class="spinner-border spinner-border-sm me-2" role="status"></div> Mengambil data log...
                              </div>
                            </div>
                          </div>
                          <div class="modal-footer bg-light border-top py-2">
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                          </div>
                        </div>
                      </div>
                    </div>

                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </main>

  </div>
</div>

                    <!-- MODAL EDIT DATA (Tampilan Menyamping / Horizontal) -->
                    <div class="modal fade text-wrap" id="modalEdit<?= $row['id']; ?>" tabindex="-1" aria-labelledby="modalEditLabel<?= $row['id']; ?>" aria-hidden="true">
                      <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content bg-white text-dark shadow">
                          
                          <!-- Header Modal -->
                          <div class="modal-header bg-light border-bottom">
                            <h5 class="modal-title fw-bold text-primary" id="modalEditLabel<?= $row['id']; ?>">
                              <i class="bi bi-pencil-square me-2"></i>Edit Kredensial Vault
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          
                          <!-- Form Input Kirim ke CRUD PHP -->
                          <form action="password_vault.php" method="POST">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= $row['id']; ?>">
                            
                            <div class="modal-body p-4">
                              <div class="row">
                                
                                <!-- === KOLOM KIRI === -->
                                <div class="col-12 col-md-6 border-end-md pb-3 pb-md-0">
                                  <!-- 1. Nama Akun / Layanan -->
                                  <div class="mb-3">
                                    <label class="form-label fw-semibold text-secondary small">Nama Akun / Layanan <span class="text-danger">*</span></label>
                                    <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($row['nama']); ?>" required>
                                  </div>

                                  <!-- 2. Dropdown Pilihan Kategori Relasional -->
                                  <div class="mb-3">
                                    <label class="form-label fw-semibold text-secondary small">Kategori Kelompok</label>
                                    <select name="kategori_id" class="form-select">
                                      <option value="">-- Tanpa Kategori --</option>
                                      <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id']; ?>" <?= ($cat['id'] == $row['kategori_id']) ? 'selected' : ''; ?>>
                                          <?= htmlspecialchars($cat['nama']); ?>
                                        </option>
                                      <?php endforeach; ?>
                                    </select>
                                  </div>

                                  <!-- 3. Tipe Layanan -->
                                  <div class="mb-3">
                                    <label class="form-label fw-semibold text-secondary small">Tipe Infrastruktur</label>
                                    <select name="tipe" class="form-select">
                                      <option value="">-- Pilih Tipe --</option>
                                      <option value="1" <?= ($row['tipe'] == 1) ? 'selected' : ''; ?>>Server</option>
                                      <option value="2" <?= ($row['tipe'] == 2) ? 'selected' : ''; ?>>Network Device</option>
                                      <option value="3" <?= ($row['tipe'] == 3) ? 'selected' : ''; ?>>Lainnya</option>
                                    </select>
                                  </div>

                                  <!-- 4. URL / Link Akses -->
                                  <div class="mb-3 mb-md-0">
                                    <label class="form-label fw-semibold text-secondary small">URL / Link Web</label>
                                    <input type="url" name="url" class="form-control" value="<?= htmlspecialchars($row['url']); ?>" placeholder="https://">
                                  </div>
                                </div>

                                <!-- === KOLOM KANAN === -->
                                <div class="col-12 col-md-6 ps-md-custom">
                                  <!-- 5. IP Address -->
                                  <div class="mb-3">
                                    <label class="form-label fw-semibold text-secondary small">IP Address</label>
                                    <input type="text" name="ip" class="form-control" value="<?= htmlspecialchars($row['ip']); ?>" placeholder="10.10.X.X">
                                  </div>

                                  <!-- 6. Username -->
                                  <div class="mb-3">
                                    <label class="form-label fw-semibold text-secondary small">Username</label>
                                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($row['username']); ?>">
                                  </div>

                                  <!-- 7. Password -->
                                  <div class="mb-3">
                                    <label class="form-label fw-semibold text-secondary small">Password / Sandi</label>
                                    <input type="text" name="password" class="form-control" value="<?= htmlspecialchars($row['password']); ?>">
                                  </div>

                                  <!-- 8. Catatan Tambahan -->
                                  <div class="mb-0">
                                    <label class="form-label fw-semibold text-secondary small">Catatan Deskripsi</label>
                                    <textarea name="catatan" class="form-control" rows="1" style="min-height: 38px;" placeholder="Detail login..."><?= htmlspecialchars($row['catatan']); ?></textarea>
                                  </div>
                                </div>

                              </div>
                            </div>

                            <!-- Footer Tombol Aksi Modal -->
                            <div class="modal-footer bg-light border-top flex-nowrap">
                              <button type="button" class="btn btn-sm btn-secondary w-50" data-bs-dismiss="modal">Batal</button>
                              <button type="submit" class="btn btn-sm btn-warning w-50 fw-semibold text-dark"><i class="bi bi-check-circle me-1"></i>Simpan</button>
                            </div>
                          </form>

                        </div>
                      </div>
                    </div>

<!-- MODAL TAMBAH DATA (Tampilan Menyamping / Horizontal) -->
<div class="modal fade text-wrap" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content bg-white text-dark shadow">
      
      <!-- Header Modal -->
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold text-primary" id="modalTambahLabel">
          <i class="bi bi-plus-circle-fill me-2"></i>Tambah Kredensial Vault Baru
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <!-- Form Input Kirim ke CRUD PHP -->
      <form action="password_vault.php" method="POST">
        <input type="hidden" name="action" value="create">
        
        <div class="modal-body p-4">
          <div class="row">
            
            <!-- === KOLOM KIRI === -->
            <div class="col-12 col-md-6 border-end-md pb-3 pb-md-0">
              <!-- 1. Nama Akun / Layanan -->
              <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Nama Akun / Layanan <span class="text-danger">*</span></label>
                <input type="text" name="nama" class="form-control" placeholder="Contoh: Winbox Mikrotik Utama" required>
              </div>

              <!-- 2. Dropdown Pilihan Kategori Relasional -->
              <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Kategori Kelompok</label>
                <select name="kategori_id" class="form-select">
                  <option value="">-- Tanpa Kategori --</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id']; ?>">
                      <?= htmlspecialchars($cat['nama']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- 3. Tipe Layanan -->
              <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Tipe Infrastruktur</label>
                <select name="tipe" class="form-select">
                  <option value="">-- Pilih Tipe --</option>
                  <option value="1">Server</option>
                  <option value="2">Network Device</option>
                  <option value="3">Lainnya</option>
                </select>
              </div>

              <!-- 4. URL / Link Akses -->
              <div class="mb-3 mb-md-0">
                <label class="form-label fw-semibold text-secondary small">URL / Link Web</label>
                <input type="url" name="url" class="form-control" placeholder="https://example.com">
              </div>
            </div>

            <!-- === KOLOM KANAN === -->
            <div class="col-12 col-md-6 ps-md-custom">
              <!-- 5. IP Address -->
              <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">IP Address</label>
                <input type="text" name="ip" class="form-control" placeholder="10.10.X.X">
              </div>

              <!-- 6. Username -->
              <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username / email">
              </div>

              <!-- 7. Password -->
              <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Password / Sandi</label>
                <input type="text" name="password" class="form-control" placeholder="Masukkan kata sandi">
              </div>

              <!-- 8. Catatan Tambahan -->
              <div class="mb-0">
                <label class="form-label fw-semibold text-secondary small">Catatan Deskripsi</label>
                <textarea name="catatan" class="form-control" rows="1" style="min-height: 38px;" placeholder="Detail login..."></textarea>
              </div>
            </div>

          </div>
        </div>

        <!-- Footer Tombol Aksi Modal -->
        <div class="modal-footer bg-light border-top flex-nowrap">
          <button type="button" class="btn btn-sm btn-secondary w-50" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-primary w-50 fw-semibold"><i class="bi bi-plus-lg me-1"></i>Tambah Data</button>
        </div>
      </form>

    </div>
  </div>
</div>

    <!-- ========================================== -->
    <!-- SCRIPT JAVASCRIPT PEMBANTU UTAMA           -->
    <!-- ========================================== -->
    <script>
    // 1. FUNGSI UNTUK KLIK LIHAT / SEMBUNYIKAN PASSWORD UTAMA DI TABEL
    function togglePassword(id) {
        var input = document.getElementById('passInput' + id);
        var icon = document.getElementById('eyeIcon' + id);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.type = "password";
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }

    // 2. FIX FUNGSI AJAX: MENGAMBIL DATA LOG RIWAYAT TANPA REFRESH
    function loadHistory(vaultId) {
    const container = document.getElementById('contentRiwayat' + vaultId);
    
    // Tampilkan animasi loading selagi mengambil data terbaru
    container.innerHTML = `
        <div class="text-center py-3 text-muted">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div> Mengambil data log...
        </div>`;
    
    // Menembak data dari file handler get_password_history.php
    fetch('get_password_history.php?vault_id=' + vaultId)
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.text();
        })
        .then(data => {
            // Memasukkan tabel riwayat ke dalam modal
            container.innerHTML = data;
        })
        .catch(error => {
            container.innerHTML = '<div class="alert alert-danger p-2 small m-0 text-center">Gagal memuat log riwayat.</div>';
        });
}

    // 3. FUNGSI UNTUK LIHAT / SEMBUNYIKAN PASSWORD LAMA DI DALAM MODAL RIWAYAT
    function toggleHistoryPassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById('icon_' + inputId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.type = "password";
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }

    // 4. FUNGSI UNTUK MENYALIN PASSWORD LAMA LANGSUNG KE CLIPBOARD HAPE/LAPTOP
    function copyToClipboard(inputId) {
        const input = document.getElementById(inputId);
        navigator.clipboard.writeText(input.value).then(() => {
            alert("Password lama berhasil disalin!");
        }).catch(err => {
            alert("Gagal menyalin teks: " + err);
        });
    }
    </script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
