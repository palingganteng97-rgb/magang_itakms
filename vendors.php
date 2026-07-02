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
        .sidebar { background-color: #212529; color: white; min-height: 100vh; position: sticky; top: 0; }
        .sidebar a { color: #adb5bd; text-decoration: none; display: block; }
        .sidebar a:hover, .sidebar a.active { color: white; background-color: #343a40; }

        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
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
        <!-- MAINTENANCE -->
        <li class="nav-item">
        <a href="maintenance.php" class="nav-link text-white p-2 rounded">
            <i class="bi bi-wrench-adjustable-circle me-2"></i> Maintenance
        </a>
        </li>
        <!-- KNOWLEDGE CATEGORIES (Tampil di semua device) --> 
        <li class="nav-item"> 
            <a href="knowledge_categories.php" class="nav-link <?= ($currentPage == 'knowledge_categories.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded"> 
                <i class="bi bi-tags-fill me-2"></i> <span>Knowledge Categories</span>
            </a> 
        </li> 
        <!-- KNOWLEDGE ARTICLES (Tampil di semua device) --> 
        <li class="nav-item"> 
            <a href="knowledge_articles.php" class="nav-link <?= ($currentPage == 'knowledge_articles.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded"> 
                <i class="bi bi-file-earmark-text-fill me-2"></i> <span>Knowledge Articles</span>
            </a> 
        </li> 
        <!-- SOFTWARE LICENSES (Khusus Mobile & Device Kecil) --> 
        <li class="nav-item d-md-none"> 
            <a href="software_licenses.php" class="nav-link <?= ($currentPage == 'software_licenses.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded d-flex align-items-center"> 
                <i class="bi bi-key-fill me-2"></i> 
                <span>Software Licenses</span> 
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
        <!-- MAINTENANCE -->
        <li class="nav-item">
          <a href="maintenance.php" class="nav-link <?= ($currentPage == 'maintenance.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
            <i class="bi bi-wrench-adjustable-circle me-2"></i> Maintenance
          </a>
        </li>
        <!-- KNOWLEDGE CATEGORIES (Tampil di semua device) --> 
        <li class="nav-item"> 
            <a href="knowledge_categories.php" class="nav-link <?= ($currentPage == 'knowledge_categories.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded"> 
                <i class="bi bi-tags-fill me-2"></i> <span>Knowledge Categories</span>
            </a> 
        </li> 
        <!-- KNOWLEDGE ARTICLES (Tampil di semua device) --> 
        <li class="nav-item"> 
            <a href="knowledge_articles.php" class="nav-link <?= ($currentPage == 'knowledge_articles.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded"> 
                <i class="bi bi-file-earmark-text-fill me-2"></i> <span>Knowledge Articles</span>
            </a> 
        </li> 
        <!-- SOFTWARE LICENSES (Langsung tampil di Desktop & Mobile) --> 
        <li class="nav-item"> 
            <a href="software_licenses.php" class="nav-link <?= ($currentPage == 'software_licenses.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded d-flex align-items-center"> 
                <i class="bi bi-key-fill me-2"></i> 
                <span>Software Licenses</span> 
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

<!-- AREA UTAMA KONTEN (Penyelarasan Grid Desktop Sesuai Ketebalan Ukuran Sidebar) -->
<main class="col-12 col-md-8 col-lg-9 offset-md-4 offset-lg-3 px-2 px-md-4 pt-4" style="min-width: 0; overflow: hidden;">

  <!-- Header Halaman Utama (Diletakkan di luar card agar rapi) -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center pt-3 pb-2 mb-3 border-bottom gap-2">
    <div>
      <h1 class="h3 fw-bold text-dark mb-1">Master Data Vendors</h1>
      <p class="text-muted small mb-0 d-none d-sm-block">Kelola daftar perusahaan penyedia layanan, suplier perangkat keras, dan kontak rekanan TI.</p>
    </div>

  </div>

  <!-- Notifikasi Flash Status CRUD (Diletakkan di atas Card Konten Utama) -->
  <?php if(isset($_GET['status'])): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mx-0 mb-3" role="alert">
        <?php
          if($_GET['status'] == 'success_add') echo '<i class="bi bi-check-circle-fill me-2"></i> Data vendor baru berhasil ditambahkan!';
          if($_GET['status'] == 'success_update') echo '<i class="bi bi-check-circle-fill me-2"></i> Konfigurasi data vendor berhasil diperbarui!';
          if($_GET['status'] == 'success_delete') echo '<i class="bi bi-trash-fill me-2"></i> Data vendor berhasil dihapus dari sistem!';
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

<!-- Bagian Atas Tabel: Judul & Tombol Tambah -->
<!-- PERUBAHAN: d-flex dengan d-md-flex memastikan distribusi ruang horizontal membagi elemen ke kiri dan kanan -->
<div class="d-flex justify-content-between align-items-center gap-2 mb-3 mb-md-4">
  
  <!-- Sisi Kiri: Judul Halaman -->
  <h5 class="mb-0 text-dark fw-bold d-flex align-items-center">
    <i class="bi bi-building-fill me-2 text-primary"></i> Rekanan Penyedia Barang
  </h5>
  
  <!-- Sisi Kanan: Tombol Tambah Vendor -->
  <!-- PERUBAHAN FIX: Menggunakan w-auto agar tombol menciut pas di desktop, dan d-inline-flex untuk mengunci ukuran konten -->
  <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 py-2 py-sm-1.5 d-inline-flex align-items-center justify-content-center gap-2 shadow-sm w-auto" data-bs-toggle="modal" data-bs-target="#modalAddVendor">
      <i class="bi bi-plus-lg"></i> Tambah Vendor
  </button>

</div>

<!-- Tabel Data Vendors (Responsif Total & Terkunci w-100) -->
    <!-- PERUBAHAN STRUKTUR: Mengamankan container tabel responsif agar mendengarkan perintah scroll dengan baik -->
    <div class="table-responsive w-100 rounded-3 border" style="overflow-x: auto; -webkit-overflow-scrolling: touch; display: block;">
      <!-- Ditambahkan text-nowrap agar kolom teratur horizontal, w-100 agar memenuhi container -->
      <table class="table table-striped table-hover align-middle mb-0 text-nowrap w-100">
        <thead class="table-light border-bottom">
          <tr>
            <th class="ps-3" style="width: 70px;">No</th>
            <th>Nama Perusahaan</th>
            <th>PIC Rekanan</th>
            <th>No. Telepon</th>
            <th>Alamat Email / Website</th>
            <th>Status</th>
            <th class="text-center" style="width: 100px;">Aksi</th>
            <th class="text-center" style="width: 140px;">Vendor Contacts</th>
            <th class="text-center pe-3" style="width: 120px;">Vendor API</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($vendors)): ?>
              <tr>
                <!-- PERUBAHAN: Nilai colspan diubah menjadi 9 agar penuh menutup seluruh kolom baru -->
                <td colspan="9" class="text-center text-muted py-5" style="white-space: normal;">
                  <i class="bi bi-building-exclamation display-4 d-block mb-3 text-secondary opacity-50"></i>
                  <span class="d-block fw-semibold text-dark mb-1">Belum Ada Data Vendor</span>
                  <span class="small text-muted">Klik tombol "+ Tambah Vendor" untuk memasukkan data rekanan pertama Anda.</span>
                </td>
              </tr>
          <?php else: $no = 1 + $offset; foreach ($vendors as $v): ?>
              <tr>
                <td class="ps-3 fw-bold text-muted"><?= $no++; ?></td>
                <td class="fw-bold text-dark"><?= htmlspecialchars($v['nama'] ?? '-'); ?></td>
                <td><i class="bi bi-person me-1 text-secondary"></i> <?= htmlspecialchars($v['pic'] ?? '-'); ?></td>
                <td><code class="text-dark bg-light px-2 py-1 rounded border small"><?= htmlspecialchars($v['telepon'] ?? '-'); ?></code></td>
                <td>
                  <div class="fw-semibold text-dark mb-0 small"><?= htmlspecialchars($v['email'] ?? '-'); ?></div>
                  <small class="text-muted text-decoration-underline"><?= htmlspecialchars($v['website'] ?? '-'); ?></small>
                </td>
                <td>
                  <?php if(($v['status'] ?? 0) == 1): ?>
                      <span class="badge bg-success-subtle text-success border border-success px-2.5 py-1.5 rounded-pill">Aktif</span>
                  <?php else: ?>
                      <span class="badge bg-danger-subtle text-danger border border-danger px-2.5 py-1.5 rounded-pill">Non-Aktif</span>
                  <?php endif; ?>
                </td>
                
                <!-- KOLOM 7: Aksi Utama (Edit & Hapus) -->
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <!-- Tombol Edit Data -->
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
                    <!-- Tombol Hapus Data -->
                    <a href="proses_vendor.php?action=delete&id=<?= $v['id']; ?>" 
                       class="btn btn-outline-danger border-0" 
                       onclick="return confirm('Apakah Anda yakin ingin menghapus data vendor ini?')" 
                       title="Hapus Vendor">
                        <i class="bi bi-trash3"></i>
                    </a>
                  </div>
                </td>

                <!-- PERUBAHAN: KOLOM 8: Tombol Akses Vendor Contacts -->
                <td class="text-center">
                  <a href="vendor_contacts.php?vendor_id=<?= $v['id']; ?>" class="btn btn-sm btn-primary rounded-3 px-3 shadow-sm d-inline-flex align-items-center gap-1">
                    <i class="bi bi-person-lines-fill"></i> Kontak
                  </a>
                </td>

                <!-- PERUBAHAN: KOLOM 9: Tombol Akses Vendor API -->
                <td class="text-center pe-3">
                  <a href="vendor_apis.php?vendor_id=<?= $v['id']; ?>" class="btn btn-sm text-white rounded-3 px-3 shadow-sm d-inline-flex align-items-center gap-1" style="background-color: #6f42c1;">
                    <i class="bi bi-code-slash"></i> API
                  </a>
                </td>

              </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div> <!-- /.table-responsive -->
    
  </div> <!-- /.card -->

</main> <!-- Penutup tag main Anda -->

  </div> <!-- Penutup tag row bawaan template sidebar Anda -->
</div> <!-- Penutup tag container-fluid bawaan template sidebar Anda -->

<!-- ========================================== -->
<!-- REVISI: MODAL TAMBAH VENDOR YANG RAPI     -->
<!-- ========================================== -->
<div class="modal fade" id="modalAddVendor" tabindex="-1" aria-labelledby="modalAddVendorLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered"> <!-- Menggunakan modal-lg agar lebih lebar ke samping -->
    <div class="modal-content rounded-4 border-0 shadow overflow-hidden">
      
      <div class="modal-header bg-primary text-white py-3 px-4 border-0">
        <h5 class="modal-title fw-bold fs-5" id="modalAddVendorLabel">
          <i class="bi bi-building-add me-2 text-warning"></i> Tambah Vendor Baru
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form action="proses_vendor.php" method="POST">
        <input type="hidden" name="action" value="add_vendor">
        
        <div class="modal-body p-4">
          <!-- BARIS 1: Nama Vendor & PIC -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6">
              <label class="form-label fw-bold small text-secondary mb-1">Nama Perusahaan / Vendor <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-building"></i></span>
                <input type="text" name="nama" class="form-control rounded-end-3 bg-light-subtle" placeholder="Contoh: PT. Telekomunikasi Indonesia" required>
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label fw-bold small text-secondary mb-1">PIC Rekanan (Contact Person)</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person"></i></span>
                <input type="text" name="pic" class="form-control rounded-end-3 bg-light-subtle" placeholder="Contoh: Ahmad Subarjo">
              </div>
            </div>
          </div>
          
          <!-- BARIS 2: No. Telepon & Email -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6">
              <label class="form-label fw-bold small text-secondary mb-1">No. Telepon / WhatsApp</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-telephone"></i></span>
                <input type="text" name="telepon" class="form-control rounded-end-3 bg-light-subtle" placeholder="Contoh: 08123456789">
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label fw-bold small text-secondary mb-1">Alamat Email</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control rounded-end-3 bg-light-subtle" placeholder="Contoh: support@vendor.com">
              </div>
            </div>
          </div>
          
          <!-- BARIS 3: Website & Status Operasional -->
          <div class="row g-3 mb-0">
            <div class="col-12 col-sm-6">
              <label class="form-label fw-bold small text-secondary mb-1">Alamat Website URL</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-globe"></i></span>
                <input type="text" name="website" class="form-control rounded-end-3 bg-light-subtle" placeholder="Contoh: https://vendor.com">
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label fw-bold small text-secondary mb-1">Status Operasional</label>
              <select name="status" class="form-select rounded-3 bg-light-subtle">
                <option value="1" selected>Aktif</option>
                <option value="0">Non-Aktif</option>
              </select>
            </div>
          </div>
          
        </div>
        
        <div class="modal-footer bg-light px-4 py-3 border-top border-light-subtle">
          <button type="button" class="btn btn-light border rounded-3 px-3 btn-sm fw-semibold" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary rounded-3 px-4 btn-sm fw-bold shadow-sm"><i class="bi bi-save me-1"></i> Simpan Vendor</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- 2. MODAL EDIT VENDOR                       -->
<!-- ========================================== -->
<div class="modal fade" id="modalEditVendor" tabindex="-1" aria-labelledby="modalEditVendorLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered"> <!-- Menggunakan modal-lg agar seimbang -->
    <div class="modal-content rounded-4 border-0 shadow overflow-hidden">
      
      <div class="modal-header bg-warning text-dark py-3 px-4 border-0">
        <h5 class="modal-title fw-bold fs-5" id="modalEditVendorLabel">
          <i class="bi bi-pencil-square me-2"></i> Ubah Data Vendor
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="dark" aria-label="Close"></button>
      </div>
      
      <form action="proses_vendor.php" method="POST">
        <input type="hidden" name="action" value="edit_vendor">
        <input type="hidden" name="id" id="edit_id">
        
        <div class="modal-body p-4">
          <!-- BARIS 1: Nama Vendor & PIC -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6">
              <label class="form-label fw-bold small text-secondary mb-1">Nama Perusahaan / Vendor <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-building"></i></span>
                <input type="text" name="nama" id="edit_nama" class="form-control rounded-end-3 bg-light-subtle" required>
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label fw-bold small text-secondary mb-1">PIC Rekanan (Contact Person)</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person"></i></span>
                <input type="text" name="pic" id="edit_pic" class="form-control rounded-end-3 bg-light-subtle">
              </div>
            </div>
          </div>
          
          <!-- BARIS 2: No. Telepon & Email -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6">
              <label class="form-label fw-bold small text-secondary mb-1">No. Telepon / WhatsApp</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-telephone"></i></span>
                <input type="text" name="telepon" id="edit_telepon" class="form-control rounded-end-3 bg-light-subtle">
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label fw-bold small text-secondary mb-1">Alamat Email</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" id="edit_email" class="form-control rounded-end-3 bg-light-subtle">
              </div>
            </div>
          </div>
          
          <!-- BARIS 3: Website & Status Operasional -->
          <div class="row g-3 mb-0">
            <div class="col-12 col-sm-6">
              <label class="form-label fw-bold small text-secondary mb-1">Alamat Website URL</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-globe"></i></span>
                <input type="text" name="website" id="edit_website" class="form-control rounded-end-3 bg-light-subtle">
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label fw-bold small text-secondary mb-1">Status Operasional</label>
              <select name="status" id="edit_status" class="form-select rounded-3 bg-light-subtle">
                <option value="1">Aktif</option>
                <option value="0">Non-Aktif</option>
              </select>
            </div>
          </div>
          
        </div>
        
        <div class="modal-footer bg-light px-4 py-3 border-top border-light-subtle">
          <button type="button" class="btn btn-light border rounded-3 px-3 btn-sm fw-semibold" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning rounded-3 px-4 btn-sm fw-bold text-dark shadow-sm"><i class="bi bi-arrow-clockwise me-1"></i> Perbarui Data</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- 3. SCRIPT JAVASCRIPT AUTOMATION MAPPER     -->
<!-- ========================================== -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Tangkap elemen modal edit bootstrap
    const modalEditVendor = document.getElementById('modalEditVendor');
    
    if (modalEditVendor) {
        modalEditVendor.addEventListener('show.bs.modal', function (event) {
            // Tombol pemicu yang baru saja diklik oleh pengguna
            const button = event.relatedTarget;
            
            // Ekstrak nilai dari atribut data-* target HTML di baris tabel
            const id = button.getAttribute('data-id');
            const nama = button.getAttribute('data-nama');
            const pic = button.getAttribute('data-pic');
            const telepon = button.getAttribute('data-telepon');
            const email = button.getAttribute('data-email');
            const website = button.getAttribute('data-website');
            const status = button.getAttribute('data-status');
            
            // Masukkan data hasil ekstrak ke elemen form input modal edit
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_pic').value = pic;
            document.getElementById('edit_telepon').value = telepon;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_website').value = website;
            document.getElementById('edit_status').value = status;
        });
    }
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
