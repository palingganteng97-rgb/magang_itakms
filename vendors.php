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

    // 2. Ambil parameter pencarian jika ada (Opsional untuk fitur Cari Vendor)
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    // 3. Menghitung total baris data untuk kebutuhan pagination
    if (!empty($search)) {
        $countSql = "SELECT COUNT(*) FROM vendors WHERE nama LIKE :search OR pic LIKE :search";
        $countStmt = $conn->prepare($countSql);
        $countStmt->execute([':search' => "%$search%"]);
    } else {
        $countSql = "SELECT COUNT(*) FROM vendors";
        $countStmt = $conn->query($countSql);
    }
    $totalRows = $countStmt->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);

    // 4. Query utama mengambil data dari tabel vendors (dengan batasan LIMIT & OFFSET)
    if (!empty($search)) {
        $sql = "SELECT * FROM vendors WHERE nama LIKE :search OR pic LIKE :search ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    } else {
        $sql = "SELECT * FROM vendors ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $conn->prepare($sql);
    }
    
    // Bind nilai limit dan offset sebagai integer untuk kepatuhan PDO Strict Mode
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    // Menampung hasil query ke dalam variabel array $vendors
    $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Menampilkan pesan error jika koneksi IP 10.10.6.59 atau query mengalami kendala
    die("Koneksi atau Query Database Gagal: " . $e->getMessage());
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
          <a href="vendors.php" class="nav-link active bg-primary <?= ($currentPage == 'vendors.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
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
            <a href="vendors.php" class="nav-link active bg-primary <?= ($currentPage == 'vendors.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
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

<!-- AREA UTAMA KONTEN (Gunakan pembungkus ini agar susunan halaman tidak bergeser) -->
<main class="col-12 col-md-8 col-lg-9 ms-sm-auto ms-md-auto px-md-4 pt-4 offset-md-4 offset-lg-3">

  <!-- Header Halaman -->
  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
      <h1 class="h2 fs-4 fs-md-2 mb-1">Master Data Vendors</h1>
      <p class="text-muted small d-none d-sm-block">Kelola daftar perusahaan penyedia layanan, suplier perangkat keras, dan kontak rekanan TI.</p>
    </div>

  <!-- Notifikasi Flash Status CRUD -->
  <?php if(isset($_GET['status'])): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
        <?php
          if($_GET['status'] == 'success_add') echo '<i class="bi bi-check-circle-fill me-2"></i> Data vendor baru berhasil ditambahkan!';
          if($_GET['status'] == 'success_update') echo '<i class="bi bi-check-circle-fill me-2"></i> Konfigurasi data vendor berhasil diperbarui!';
          if($_GET['status'] == 'success_delete') echo '<i class="bi bi-trash-fill me-2"></i> Data vendor berhasil dihapus dari sistem!';
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Wadah Konten Utama / Tabel Card -->
  <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4 bg-white p-3">
    
    <!-- Bagian Atas Tabel: Judul & Tombol Tambah -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
      <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-building-fill me-2 text-primary"></i> Rekanan Penyedia Barang</h5>
      <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalAddVendor">
          <i class="bi bi-plus-lg"></i> Tambah Vendor
      </button>
    </div>

    <!-- Tabel Data Vendors (Responsif Mobile) -->
    <div class="table-responsive w-100" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
      <table class="table table-hover align-middle mb-0 text-nowrap">
        <thead class="table-light">
          <tr>
            <th class="ps-3" style="width: 60px;">No</th>
            <th>Nama Perusahaan</th>
            <th>PIC Rekanan</th>
            <th>No. Telepon</th>
            <th>Alamat Email / Website</th>
            <th>Status</th>
            <th class="text-end pe-3" style="width: 120px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($vendors)): ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-5" style="white-space: normal;">
                  <i class="bi bi-building-exclamation display-5 d-block mb-2 text-secondary"></i>
                  Belum ada data mitra vendor terdaftar dalam sistem.
                </td>
              </tr>
          <?php else: $no = 1; foreach ($vendors as $v): ?>
              <tr>
                <td class="ps-3 fw-bold text-muted"><?= $no++; ?></td>
                <td class="fw-semibold text-dark"><?= htmlspecialchars($v['nama'] ?? '-'); ?></td>
                <td><i class="bi bi-person me-1 text-secondary"></i> <?= htmlspecialchars($v['pic'] ?? '-'); ?></td>
                <td><code class="text-dark"><?= htmlspecialchars($v['telepon'] ?? '-'); ?></code></td>
                <td>
                  <div class="small text-dark mb-0"><?= htmlspecialchars($v['email'] ?? '-'); ?></div>
                  <small class="text-muted"><?= htmlspecialchars($v['website'] ?? '-'); ?></small>
                </td>
                <td>
                  <?php if(($v['status'] ?? 0) == 1): ?>
                      <span class="badge bg-success-subtle text-success border border-success px-2.5 py-1.5 rounded-pill">Aktif</span>
                  <?php else: ?>
                      <span class="badge bg-danger-subtle text-danger border border-danger px-2.5 py-1.5 rounded-pill">Non-Aktif</span>
                  <?php endif; ?>
                </td>
                <td class="text-end pe-3">
                  <div class="btn-group btn-group-sm">
                    <!-- Tombol Edit: Melempar data lengkap kolom ke atribut HTML modal -->
                    <button type="button" class="btn btn-outline-warning border-0" 
                            data-bs-toggle="modal" 
                            data-bs-target="#modalEditVendor"
                            data-id="<?= $v['id']; ?>"
                            data-nama="<?= htmlspecialchars($v['nama'] ?? ''); ?>"
                            data-pic="<?= htmlspecialchars($v['pic'] ?? ''); ?>"
                            data-telepon="<?= htmlspecialchars($v['telepon'] ?? ''); ?>"
                            data-email="<?= htmlspecialchars($v['email'] ?? ''); ?>"
                            data-website="<?= htmlspecialchars($v['website'] ?? ''); ?>"
                            data-status="<?= $v['status']; ?>"
                            title="Ubah Data Vendor">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <!-- Tombol Hapus -->
                    <a href="proses_vendor.php?action=delete&id=<?= $v['id']; ?>" 
                       class="btn btn-sm btn-outline-danger border-0" 
                       onclick="return confirm('Apakah Anda yakin ingin menghapus data vendor ini?')" 
                       title="Hapus Vendor">
                        <i class="bi bi-trash3"></i>
                    </a>
                  </div>
                </td>
              </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div> <!-- /.table-responsive -->
  </div> <!-- /.card -->

</main>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
