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

<!-- SIDEBAR MOBILE (OFFCANVAS) -->
<div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mobileSidebarLabel"><i class="bi bi-speedometer2"></i> ITAKMS</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <nav class="sidebar p-3 d-flex flex-column" style="min-height: calc(100vh - 56px);">
            <ul class="nav flex-column gap-2">
                <!-- 1. Dashboard -->
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
                </li>
                <!-- 2. Manajemen Roles -->
                <li class="nav-item">
                    <a href="roles.php" class="nav-link p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Manajemen Roles</a>
                </li>
                <!-- 3. Manajemen Bangunan & Ruang -->
                <li class="nav-item">
                    <a href="relasi.php" class="nav-link p-2 rounded text-nowrap" style="overflow: hidden; text-overflow: ellipsis;">
                        <i class="bi bi-diagram-3 me-2"></i> Manajemen Bangunan & Ruang
                    </a>
                </li>
                <!-- 4. Assets (STATUS ACTIVE PINDAH KE SINI) -->
                <li class="nav-item">
                    <a href="asset.php" class="nav-link active p-2 rounded"><i class="bi bi-boxes me-2"></i> Assets</a>
                </li>
                <!-- 5. User Profil -->
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

<!-- SIDEBAR DESKTOP -->
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-4 col-lg-3 d-none d-md-flex flex-column sidebar p-3">
            <h4 class="text-center mb-4 text-warning"><i class="bi bi-speedometer2"></i> ITAKMS</h4>
            <ul class="nav flex-column gap-2">
                <!-- 1. Dashboard -->
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
                </li>
                <!-- 2. Manajemen Roles -->
                <li class="nav-item">
                    <a href="roles.php" class="nav-link p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Manajemen Roles</a>
                </li>
                <!-- 3. Manajemen Bangunan & Ruang -->
                <li class="nav-item">
                    <a href="relasi.php" class="nav-link p-2 rounded text-nowrap" style="overflow: hidden; text-overflow: ellipsis;" title="Manajemen Bangunan & Ruang">
                        <i class="bi bi-diagram-3 me-2"></i> Manajemen Bangunan & Ruang
                    </a>
                </li>
                <!-- 4. Assets (STATUS ACTIVE PINDAH KE SINI) -->
                <li class="nav-item">
                    <a href="asset.php" class="nav-link active p-2 rounded"><i class="bi bi-boxes me-2"></i> Assets</a>
                </li>
                <!-- 5. User Profil -->
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
