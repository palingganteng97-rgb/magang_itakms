<?php
require_once __DIR__ . '/auth.php';
require_login();

// 1. Konfigurasi Database
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

// Pagination sederhana untuk tabel
$perPage = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Statistik (ambil agregat saja, bukan fetchAll semua data)
    $stmtStats = $conn->prepare("
        SELECT
            COUNT(*) AS total_users,
            SUM(status = 1) AS total_aktif
        FROM users
    ");
    $stmtStats->execute();
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

    $total_users = (int)($stats['total_users'] ?? 0);
    $total_aktif = (int)($stats['total_aktif'] ?? 0);
    $total_non_aktif = $total_users - $total_aktif;

// 3. (Tidak ada query tabel users, dashboard hanya menampilkan statistik)

} catch(PDOException $e) {
    echo "Koneksi gagal: " . $e->getMessage();
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
        .sidebar { min-height: 100vh; background-color: #212529; color: white; }
        .sidebar a { color: #adb5bd; text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { color: white; background-color: #343a40; }
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

<!-- SIDEBAR MOBILE (OFFCANVAS) KHUSUS UNTUK DASHBOARD.PHP -->
<div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="mobileSidebarLabel"><i class="bi bi-speedometer2"></i> ITAKMS</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0">
    <nav class="sidebar p-3 d-flex flex-column" style="min-height: calc(100vh - 56px);">
      <ul class="nav flex-column gap-2">
        <!-- Dashboard Aktif -->
        <li class="nav-item">
          <a href="dashboard.php" class="nav-link active bg-primary text-white p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
        </li>
        <li class="nav-item">
          <a href="roles.php" class="nav-link p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Manajemen Roles</a>
        </li>
        <li class="nav-item">
          <a href="relasi.php" class="nav-link p-2 rounded text-nowrap" style="overflow: hidden; text-overflow: ellipsis;">
            <i class="bi bi-diagram-3 me-2"></i> Manajemen Bangunan & Ruang
          </a>
        </li>
        <li class="nav-item">
          <a href="assets.php" class="nav-link p-2 rounded"><i class="bi bi-folder2-open me-2"></i> Assets</a>
        </li>
        <li class="nav-item">
          <a href="manajemen_asset.php" class="nav-link p-2 rounded"><i class="bi bi-boxes me-2"></i> Manajemen Asset</a>
        </li>
        <li class="nav-item">
          <a href="asset_movements.php" class="nav-link p-2 rounded"><i class="bi bi-arrow-left-right me-2"></i> Log Perpindahan</a>
        </li>
        <!-- Menu Server Baru (Mobile) -->
        <li class="nav-item">
          <a href="server.php" class="nav-link p-2 rounded"><i class="bi bi-hdd-network me-2"></i> Server</a>
        </li>
        <!-- Menu Network Device (Mobile Baru) -->
        <li class="nav-item">
          <a href="network_device.php" class="nav-link p-2 rounded"><i class="bi bi-router me-2"></i> Network Device</a>
        </li>
        <!-- Menu Network Port (Mobile Baru) -->
        <li class="nav-item">
          <a href="network_port.php" class="nav-link p-2 rounded"><i class="bi bi-ethernet me-2"></i> Network Port</a>
        </li>
        <li class="nav-item">
          <a href="user.php" class="nav-link p-2 rounded"><i class="bi bi-person-lines-fill me-2"></i> User Profil</a>
        </li>
      </ul>
      <div class="mt-auto pt-3">
        <ul class="nav flex-column gap-2">
          <li class="nav-item">
            <a href="logout.php" class="nav-link p-2 rounded" style="color:#dc3545 !important;">
              <i class="bi bi-box-arrow-right me-2" style="color:#dc3545 !important;"></i> Logout
            </a>
          </li>
        </ul>
      </div>
    </nav>
  </div>
</div>

<!-- SIDEBAR DESKTOP KHUSUS UNTUK DASHBOARD.PHP -->
<div class="container-fluid">
  <div class="row">
    <nav class="col-md-4 col-lg-3 d-none d-md-flex flex-column sidebar p-3 text-bg-dark" style="min-height: 100vh;">
      <h4 class="text-center mb-4 text-warning"><i class="bi bi-speedometer2"></i> ITAKMS</h4>
      <ul class="nav flex-column gap-2">
        <!-- Dashboard Aktif -->
        <li class="nav-item">
          <a href="dashboard.php" class="nav-link active bg-primary text-white p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
        </li>
        <li class="nav-item">
          <a href="roles.php" class="nav-link p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Manajemen Roles</a>
        </li>
        <li class="nav-item">
          <a href="relasi.php" class="nav-link p-2 rounded text-nowrap" style="overflow: hidden; text-overflow: ellipsis;" title="Manajemen Bangunan & Ruang">
            <i class="bi bi-diagram-3 me-2"></i> Manajemen Bangunan & Ruang
          </a>
        </li>
        <li class="nav-item">
          <a href="assets.php" class="nav-link p-2 rounded"><i class="bi bi-folder2-open me-2"></i> Assets</a>
        </li>
        <li class="nav-item">
          <a href="manajemen_asset.php" class="nav-link p-2 rounded"><i class="bi bi-boxes me-2"></i> Manajemen Asset</a>
        </li>
        <li class="nav-item">
          <a href="asset_movements.php" class="nav-link p-2 rounded"><i class="bi bi-arrow-left-right me-2"></i> Log Perpindahan</a>
        </li>
        <!-- Menu Server Baru (Desktop) -->
        <li class="nav-item">
          <a href="server.php" class="nav-link p-2 rounded"><i class="bi bi-hdd-network me-2"></i> Server</a>
        </li>
        <!-- Menu Network Device (Desktop Baru) -->
        <li class="nav-item">
          <a href="network_device.php" class="nav-link p-2 rounded"><i class="bi bi-router me-2"></i> Network Device</a>
        </li>
        <!-- Menu Network Port (Desktop Baru) -->
        <li class="nav-item">
          <a href="network_port.php" class="nav-link p-2 rounded"><i class="bi bi-ethernet me-2"></i> Network Port</a>
        </li>
        <li class="nav-item">
          <a href="user.php" class="nav-link p-2 rounded"><i class="bi bi-person-lines-fill me-2"></i> User Profil</a>
        </li>
      </ul>
      <div class="mt-auto pt-3 border-top border-secondary w-100">
        <ul class="nav flex-column gap-2">
          <li class="nav-item">
            <a href="logout.php" class="nav-link p-2 rounded" style="color:#dc3545 !important;">
              <i class="bi bi-box-arrow-right me-2" style="color:#dc3545 !important;"></i> Logout
            </a>
          </li>
        </ul>
      </div>
    </nav>

        <!-- MAIN CONTENT (Konten Utama) -->
        <main class="col-md-9 col-12 px-2 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard Sistem</h1>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary p-2">Sesi Admin</span>
                </div>


            </div>

            <!-- STATISTIC CARDS (Ringkasan Data) -->
            <div class="row mb-4 gx-2">
                <div class="col-md-4">
                    <div class="card bg-primary text-white mb-3 shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white-50">Total Users</h6>
                                <h2 class="card-text fw-bold"><?= $total_users ?></h2>
                            </div>
                            <i class="bi bi-people fs-1 text-white-50"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card bg-success text-white mb-3 shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white-50">User Aktif</h6>
                                <h2 class="card-text fw-bold"><?= $total_aktif ?></h2>
                            </div>
                            <i class="bi bi-person-check fs-1 text-white-50"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card bg-danger text-white mb-3 shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white-50">User Non-Aktif</h6>
                                <h2 class="card-text fw-bold"><?= $total_non_aktif ?></h2>
                            </div>
                            <i class="bi bi-person-x fs-1 text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>


</main>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
