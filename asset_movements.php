<?php
// =========================================================================
// LOGIKA BACKEND UTUH: asset_movements.php (PULIH TOTAL & KILAT)
// =========================================================================
require_once __DIR__ . '/auth.php';
require_login();

// 1. Konfigurasi Database Kredensial Anda
$host     = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Mengambil kata kunci pencarian dari kolom teks filter di halaman depan
    $search_keyword = isset($_GET['search_keyword']) ? trim($_GET['search_keyword']) : '';
    $where_clause = "";
    $params = [];

    // Logika jika user mengetik sesuatu untuk mencari log data
    if (!empty($search_keyword)) {
        $where_clause = " WHERE a.nama LIKE :search 
                          OR a.kode_asset LIKE :search 
                          OR am.alasan LIKE :search";
        $params[':search'] = "%$search_keyword%";
    }

    // 2. QUERY UTAMA: DITAMBAHKAN GROUP BY am.id AGAR DATA TIDAK GANDA / DOUBLE
    $query = "SELECT 
                am.id, am.tanggal, am.alasan,
                a.kode_asset, a.nama AS nama_asset,
                r1.nama AS dari_ruangan, r2.nama AS ke_ruangan
              FROM asset_movements am
              LEFT JOIN assets a ON am.asset_id = a.id
              LEFT JOIN rooms r1 ON am.room_from = r1.id
              LEFT JOIN rooms r2 ON am.room_to = r2.id"
              . $where_clause . 
              " GROUP BY am.id 
                ORDER BY am.id DESC LIMIT 100";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $movements_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("<div class='alert alert-danger m-3'>Koneksi database bermasalah: " . $e->getMessage() . "</div>");
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

<!-- SIDEBAR MOBILE (OFFCANVAS) KHUSUS UNTUK ASSET_MOVEMENTS.PHP -->
<div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="mobileSidebarLabel"><i class="bi bi-speedometer2"></i> ITAKMS</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0">
    <nav class="sidebar p-3 d-flex flex-column" style="min-height: calc(100vh - 56px);">
      <ul class="nav flex-column gap-2">
        <li class="nav-item">
          <a href="dashboard.php" class="nav-link p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
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
        <!-- REKOMENDASI AKTIF MOBILE -->
        <li class="nav-item">
          <a href="asset_movements.php" class="nav-link active bg-primary text-white p-2 rounded">
            <i class="bi bi-arrow-left-right me-2"></i> Log Perpindahan
          </a>
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

<!-- SIDEBAR DESKTOP KHUSUS UNTUK ASSET_MOVEMENTS.PHP -->
<div class="container-fluid">
  <div class="row">
    <nav class="col-md-4 col-lg-3 d-none d-md-flex flex-column sidebar p-3 text-bg-dark" style="min-height: 100vh;">
      <h4 class="text-center mb-4 text-warning"><i class="bi bi-speedometer2"></i> ITAKMS</h4>
      <ul class="nav flex-column gap-2">
        <li class="nav-item">
          <a href="dashboard.php" class="nav-link p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
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
        <!-- REKOMENDASI AKTIF DESKTOP -->
        <li class="nav-item">
          <a href="asset_movements.php" class="nav-link active bg-primary text-white p-2 rounded">
            <i class="bi bi-arrow-left-right me-2"></i> Log Perpindahan
          </a>
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

    <!-- AREA KONTEN UTAMA KANAN -->
    <main class="col-md-8 col-lg-9 p-4">
      
      <!-- Banner Judul Halaman (Tombol Kembali Sudah Dihapus) -->
      <div class="d-flex justify-content-between align-items-center pt-2 pb-2 mb-3 border-bottom">
        <div>
          <h1 class="h3 fw-bold text-dark m-0">Log Riwayat Perpindahan Asset</h1>
          <p class="text-muted small m-0">Memantau mutasi dan pergerakan lokasi inventaris perangkat ITAKMS.</p>
        </div>
      </div>

      <!-- Form Filter Pencarian -->
      <form method="GET" action="asset_movements.php" class="bg-white p-3 rounded-3 shadow-sm mb-4 border border-light">
        <div class="row g-2 align-items-end">
          <div class="col-md-9">
            <label class="form-label small fw-bold text-secondary mb-1" style="font-size:0.8rem;">Cari Riwayat Mutasi</label>
            <input type="text" name="search_keyword" class="form-control form-control-sm rounded-2" placeholder="Ketik nama asset, kode asset, atau alasan..." value="<?= htmlspecialchars($search_keyword ?? '') ?>">
          </div>
          <div class="col-md-3 d-flex gap-1">
            <button class="btn btn-sm btn-primary w-100 fw-bold rounded-2" type="submit"><i class="bi bi-search"></i> Cari Log</button>
            <a href="asset_movements.php" class="btn btn-sm btn-outline-secondary w-100 rounded-2"><i class="bi bi-arrow-clockwise"></i> Reset</a>
          </div>
        </div>
      </form>

      <!-- Tabel Riwayat Transparansi Pergerakan (Anti Melar) -->
      <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="table-responsive">
          <table class="table table-hover table-striped align-middle mb-0" style="font-size: 0.88rem; table-layout: fixed; width: 100%;">
            <thead class="table-dark">
              <tr>
                <th scope="col" class="text-center" style="width: 55px;">No</th>
                <th scope="col" style="width: 130px;">Tanggal</th>
                <th scope="col" style="width: 140px;">Kode Asset</th>
                <th scope="col" style="width: 180px;">Nama Asset</th>
                <th scope="col" class="text-center" style="width: 250px;">Alur Perpindahan</th>
                <th scope="col" style="width: 320px;">Alasan Perpindahan</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($movements_data)): ?>
                <?php $no = 1; foreach ($movements_data as $log): ?>
                  <tr>
                    <!-- Nomor -->
                    <td class="text-center fw-bold text-muted"><?= $no++; ?></td>
                    
                    <!-- Tanggal Mutasi -->
                    <td>
                      <span class="badge bg-light text-dark border px-2 py-1">
                        <i class="bi bi-calendar3 me-1 text-secondary"></i> <?= htmlspecialchars($log['tanggal']); ?>
                      </span>
                    </td>
                    
                    <!-- Identitas Perangkat -->
                    <td class="fw-monospace text-primary small text-truncate" title="<?= htmlspecialchars($log['kode_asset'] ?? '-'); ?>">
                      <?= htmlspecialchars($log['kode_asset'] ?? '-'); ?>
                    </td>
                    <td class="fw-bold text-dark text-truncate" title="<?= htmlspecialchars($log['nama_asset'] ?? 'Asset Terhapus'); ?>">
                      <?= htmlspecialchars($log['nama_asset'] ?? 'Asset Terhapus'); ?>
                    </td>
                    
                    <!-- Rute Alur Perpindahan Ruangan -->
                    <td class="text-center">
                      <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 fw-semibold d-inline-block text-truncate" style="max-width: 90px;" title="<?= !empty($log['dari_ruangan']) ? htmlspecialchars($log['dari_ruangan']) : 'Awal'; ?>">
                        <?= !empty($log['dari_ruangan']) ? htmlspecialchars($log['dari_ruangan']) : 'Awal'; ?>
                      </span>
                      <i class="bi bi-arrow-right text-primary mx-1 fw-bold"></i>
                      <span class="badge bg-success-subtle text-success border px-2 py-1 fw-bold d-inline-block text-truncate" style="max-width: 90px;" title="<?= !empty($log['ke_ruangan']) ? htmlspecialchars($log['ke_ruangan']) : '-'; ?>">
                        <?= !empty($log['ke_ruangan']) ? htmlspecialchars($log['ke_ruangan']) : '-'; ?>
                      </span>
                    </td>
                    
                    <!-- Alasan Perpindahan (Menggunakan Kotak Scroll Rapi) -->
                    <td>
                      <div class="text-muted small pe-1" style="max-height: 65px; overflow-y: auto; white-space: normal; line-height: 1.4; word-break: break-word;">
                        <?= htmlspecialchars($log['alasan'] ?? 'Tanpa catatan alasan.'); ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <!-- Kondisi Jika Data Kosong -->
                <tr>
                  <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-info-circle-fill me-1 text-secondary h5 d-block mb-2"></i>
                    Belum ada rekaman riwayat log perpindahan asset yang ditemukan.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>

  </div> <!-- Penutup Row Grid Utama -->
</div> <!-- Penutup Container-Fluid -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
