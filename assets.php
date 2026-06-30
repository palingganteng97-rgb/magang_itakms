<?php
// =========================================================================
// LOGIKA BACKEND: assets.php (SUDAH FIX UNTUK MENAMPILKAN ROOM/RUANGAN)
// =========================================================================
$host     = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $where_clause = "";
    $params = [];

    if (!empty($search)) {
        $where_clause = " WHERE a.nama LIKE :search OR a.kode_asset LIKE :search OR a.serial_number LIKE :search";
        $params[':search'] = "%$search%";
    }

    // PERBAIKAN: Menambahkan r.nama AS nama_ruangan dan LEFT JOIN rooms r
    $query = "SELECT 
                a.id, 
                a.kode_asset, 
                a.nama AS nama_asset, 
                a.serial_number, 
                a.hostname, 
                a.ip_address, 
                a.mac_address, 
                a.tanggal_beli, 
                a.garansi, 
                a.foto, 
                a.manual_book, 
                a.spesifikasi, 
                a.created_at, 
                a.updated_at,
                a.kategori_id,
                a.brand_id,
                a.room_id,
                a.status_id,
                cat.nama AS nama_kategori, 
                b.nama AS nama_brand, 
                s.nama AS nama_status,
                r.nama AS nama_ruangan       -- 1. Mengambil kolom nama dari tabel rooms
              FROM assets a
              LEFT JOIN asset_categories cat ON a.kategori_id = cat.id
              LEFT JOIN asset_brands b ON a.brand_id = b.id
              LEFT JOIN asset_statuses s ON a.status_id = s.id
              LEFT JOIN rooms r ON a.room_id = r.id" . $where_clause . " ORDER BY a.id DESC"; // 2. Menghubungkan ke tabel rooms

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $assets_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // AMBIL DATA RELASI UNTUK DROPDOWN MODAL
    $list_kategori = $conn->query("SELECT id, nama FROM asset_categories ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
    $list_brand    = $conn->query("SELECT id, nama FROM asset_brands ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
    $list_ruangan  = $conn->query("SELECT id, nama FROM rooms ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
    $list_status   = $conn->query("SELECT id, nama FROM asset_statuses ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Koneksi atau query bermasalah: " . $e->getMessage());
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
        <!-- 4. Assets (AKTIF) -->
        <li class="nav-item">
          <a href="assets.php" class="nav-link active p-2 rounded"><i class="bi bi-folder2-open me-2"></i> Assets</a>
        </li>
        <!-- 5. Manajemen Asset -->
        <li class="nav-item">
          <a href="manajemen_asset.php" class="nav-link p-2 rounded"><i class="bi bi-boxes me-2"></i> Manajemen Asset</a>
        </li>
        <!-- 6. User Profil -->
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

<!-- SIDEBAR DESKTOP & KONTEN UTAMA -->
<div class="container-fluid">
  <div class="row">
    
    <!-- 1. Sidebar Desktop (Sisi Kiri) -->
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
          <a href="assets.php" class="nav-link active p-2 rounded"><i class="bi bi-folder2-open me-2"></i> Assets</a>
        </li>
        <li class="nav-item">
          <a href="manajemen_asset.php" class="nav-link p-2 rounded"><i class="bi bi-boxes me-2"></i> Manajemen Asset</a>
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

<!-- 2. Area Konten Utama (Sisi Kanan) -->
<main class="col-md-8 col-lg-9 p-4">
  
  <!-- Judul & Tombol -->
  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Daftar Assets</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
      <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAssetModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Asset
      </button>
    </div>
  </div>

  <!-- Antarmuka Pencarian -->
  <div class="row mb-3">
    <div class="col-md-4 ms-auto">
      <form method="GET" action="assets.php">
        <div class="input-group input-group-sm">
          <input type="text" name="search" class="form-control" placeholder="Cari kode atau nama..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
          <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabel Data -->
  <div class="card shadow-sm border-0">
    <div class="table-responsive">
      <table class="table table-hover table-striped align-middle mb-0" style="font-size: 0.9rem;">
        <thead class="table-dark">
          <tr>
            <th scope="col" class="text-center" style="width: 50px;">No</th>
            <th scope="col">Kategori</th>
            <th scope="col">Brand</th>
            <th scope="col">Rooms</th>
            <th scope="col">Status</th>
            <th scope="col">Kode Asset</th>
            <th scope="col">Nama</th>
            <th scope="col" class="text-center" style="width: 120px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($assets_data)): ?>
            <?php $no = 1; foreach ($assets_data as $asset): ?>
              <tr>
                <td class="text-center fw-bold"><?= $no++; ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($asset['nama_kategori'] ?? 'Tidak ada') ?></span></td>
                <td><?= htmlspecialchars($asset['nama_brand'] ?? '-') ?></td>
                
                <!-- FIX: Memanggil nama_ruangan hasil JOIN database -->
                <td><?= htmlspecialchars($asset['nama_ruangan'] ?? '-') ?></td>
                
                <td>
                  <?php 
                    $status = strtolower($asset['nama_status'] ?? '');
                    $badge_class = 'bg-success'; 
                    if (strpos($status, 'rusak') !== false || strpos($status, 'maintenance') !== false) {
                        $badge_class = 'bg-danger';
                    } elseif (strpos($status, 'perbaikan') !== false) {
                        $badge_class = 'bg-warning text-dark';
                    }
                  ?>
                  <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($asset['nama_status'] ?? '-') ?></span>
                </td>
                <td class="fw-monospace text-primary"><?= htmlspecialchars($asset['kode_asset'] ?? '-') ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($asset['nama_asset'] ?? '-') ?></td>
                <td class="text-center">
                  <div class="btn-group gap-1">
                    <!-- 1. Tombol Detail -->
                    <button type="button" 
                            class="btn btn-xs btn-info text-white btn-detail" 
                            data-bs-toggle="modal" 
                            data-bs-target="#assetDetailModal"
                            data-id="<?= $asset['id'] ?>"
                            data-kode="<?= htmlspecialchars($asset['kode_asset'] ?? '-') ?>"
                            data-nama="<?= htmlspecialchars($asset['nama_asset'] ?? '-') ?>"
                            data-serial="<?= htmlspecialchars($asset['serial_number'] ?? '-') ?>"
                            data-hostname="<?= htmlspecialchars($asset['hostname'] ?? '-') ?>"
                            data-ip="<?= htmlspecialchars($asset['ip_address'] ?? '-') ?>"
                            data-mac="<?= htmlspecialchars($asset['mac_address'] ?? '-') ?>"
                            data-tgl="<?= htmlspecialchars($asset['tanggal_beli'] ?? '-') ?>"
                            data-garansi="<?= htmlspecialchars($asset['garansi'] ?? '-') ?>"
                            data-foto="<?= htmlspecialchars($asset['foto'] ?? 'default.jpg') ?>"
                            data-manual="<?= htmlspecialchars($asset['manual_book'] ?? '-') ?>"
                            data-spek="<?= htmlspecialchars($asset['spesifikasi'] ?? '-') ?>"
                            title="Lihat Detail">
                      <i class="bi bi-eye-fill"></i>
                    </button>

                    <!-- 2. Tombol Edit -->
                    <button type="button" 
                            class="btn btn-xs btn-warning text-dark btn-edit" 
                            data-bs-toggle="modal" 
                            data-bs-target="#editAssetModal"
                            data-id="<?= $asset['id'] ?>"
                            data-kategori="<?= $asset['kategori_id'] ?>"
                            data-brand="<?= $asset['brand_id'] ?>"
                            data-room="<?= $asset['room_id'] ?>"
                            data-status="<?= $asset['status_id'] ?>"
                            data-kode="<?= htmlspecialchars($asset['kode_asset'] ?? '-') ?>"
                            data-nama="<?= htmlspecialchars($asset['nama_asset'] ?? '-') ?>"
                            data-serial="<?= htmlspecialchars($asset['serial_number'] ?? '-') ?>"
                            data-hostname="<?= htmlspecialchars($asset['hostname'] ?? '-') ?>"
                            data-ip="<?= htmlspecialchars($asset['ip_address'] ?? '-') ?>"
                            data-mac="<?= htmlspecialchars($asset['mac_address'] ?? '-') ?>"
                            data-tgl="<?= htmlspecialchars($asset['tanggal_beli'] ?? '-') ?>"
                            data-garansi="<?= htmlspecialchars($asset['garansi'] ?? '-') ?>"
                            data-spek="<?= htmlspecialchars($asset['spesifikasi'] ?? '-') ?>"
                            title="Ubah Data">
                      <i class="bi bi-pencil-square"></i>
                    </button>

                    <!-- 3. Tombol Hapus -->
                    <a href="proses_asset.php?action=delete&id=<?= $asset['id'] ?>" 
                       class="btn btn-xs btn-danger" 
                       onclick="return confirm('Apakah Anda yakin ingin menghapus asset <?= htmlspecialchars($asset['nama_asset'] ?? '') ?> ini?');"
                       title="Hapus Data">
                      <i class="bi bi-trash-fill"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">Tidak ada data asset yang ditemukan.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</main> <!-- Penutup Utama Konten -->
</div> <!-- Penutup Row Grid -->
</div> <!-- Penutup Container-Fluid -->

<!-- MODAL DETAIL ASSET -->
<div class="modal fade" id="assetDetailModal" tabindex="-1" aria-labelledby="assetDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="assetDetailModalLabel"><i class="bi bi-info-circle-fill me-2"></i> Detail Informasi Asset</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <!-- Kolom Foto Asset -->
          <div class="col-md-4 text-center mb-3 mb-md-0 border-end border-light">
            <div class="bg-light p-2 rounded mb-2 d-flex align-items-center justify-content-center" style="height: 200px;">
              <img id="detail-foto" src="uploads/default.jpg" alt="Foto Asset" class="img-fluid rounded shadow-sm" style="max-height: 100%; object-fit: cover;">
            </div>
            <h6 id="detail-kode" class="text-primary fw-monospace mb-1">-</h6>
            <small id="detail-nama" class="text-muted fw-semibold">-</small>
          </div>
          
          <!-- Kolom Informasi Teknis & Administrasi -->
          <div class="col-md-8">
            <table class="table table-sm table-borderless align-middle" style="font-size: 0.9rem;">
              <tbody>
                <tr>
                  <th style="width: 35%;" class="text-secondary">Serial Number</th>
                  <td>: <span id="detail-serial" class="fw-semibold">-</span></td>
                </tr>
                <tr>
                  <th class="text-secondary">Hostname</th>
                  <td>: <span id="detail-hostname">-</span></td>
                </tr>
                <tr>
                  <th class="text-secondary">IP Address</th>
                  <td>: <code id="detail-ip" class="text-dark bg-light px-1 rounded">-</code></td>
                </tr>
                <tr>
                  <th class="text-secondary">MAC Address</th>
                  <td>: <code id="detail-mac" class="text-dark bg-light px-1 rounded">-</code></td>
                </tr>
                <tr>
                  <th class="text-secondary">Tanggal Beli</th>
                  <td>: <span id="detail-tgl">-</span></td>
                </tr>
                <tr>
                  <th class="text-secondary">Garansi Hingga</th>
                  <td>: <span id="detail-garansi" class="text-danger fw-semibold">-</span></td>
                </tr>
                <tr>
                  <th class="text-secondary">Manual Book</th>
                  <td>: <a id="detail-manual-link" href="#" target="_blank" class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size: 0.8rem;"><i class="bi bi-file-earmark-pdf"></i> Lihat Dokumen</a><span id="detail-manual-text" class="text-muted">-</span></td>
                </tr>
              </tbody>
            </table>
            
            <div class="mt-3">
              <label class="form-label fw-bold text-secondary mb-1" style="font-size: 0.85rem;">Spesifikasi Lengkap:</label>
              <div id="detail-spek" class="p-3 bg-light rounded text-dark border" style="font-size: 0.85rem; max-height: 120px; overflow-y: auto; white-space: pre-line;">
                -
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light py-2">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL TAMBAH ASSET (FIXED DROPDOWN RELASI) -->
<div class="modal fade" id="addAssetModal" tabindex="-1" aria-labelledby="addAssetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="addAssetModalLabel"><i class="bi bi-boxes me-2"></i> Tambah Data Asset Baru</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="proses_asset.php?action=create" method="POST" enctype="multipart/form-data">
        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
          <div class="row g-3">
            <!-- Kode & Nama -->
            <div class="col-md-6">
              <label class="form-label font-monospace" style="font-size:0.85rem;">Kode Asset <span class="text-danger">*</span></label>
              <input type="text" name="kode_asset" class="form-control form-control-sm" placeholder="Contoh: AST-2026-001" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" style="font-size:0.85rem;">Nama Asset <span class="text-danger">*</span></label>
              <input type="text" name="nama" class="form-control form-control-sm" placeholder="Nama perangkat / barang" required>
            </div>

            <!-- Bagian Dropdown Otomatis (Data Relasi) -->
            <div class="col-md-3">
              <label class="form-label" style="font-size:0.85rem;">Kategori <span class="text-danger">*</span></label>
              <select name="kategori_id" class="form-select form-select-sm" required>
                <option value="">-- Pilih Kategori --</option>
                <?php if(!empty($list_kategori)): foreach ($list_kategori as $kat): ?>
                  <option value="<?= $kat['id']; ?>"><?= htmlspecialchars($kat['nama']); ?></option>
                <?php endforeach; endif; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label" style="font-size:0.85rem;">Brand <span class="text-danger">*</span></label>
              <select name="brand_id" class="form-select form-select-sm" required>
                <option value="">-- Pilih Brand --</option>
                <?php if(!empty($list_brand)): foreach ($list_brand as $brd): ?>
                  <option value="<?= $brd['id']; ?>"><?= htmlspecialchars($brd['nama']); ?></option>
                <?php endforeach; endif; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label" style="font-size:0.85rem;">Ruangan <span class="text-danger">*</span></label>
              <select name="room_id" class="form-select form-select-sm" required>
                <option value="">-- Pilih Ruangan --</option>
                <?php if(!empty($list_ruangan)): foreach ($list_ruangan as $rm): ?>
                  <option value="<?= $rm['id']; ?>"><?= htmlspecialchars($rm['nama']); ?></option>
                <?php endforeach; endif; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label" style="font-size:0.85rem;">Status <span class="text-danger">*</span></label>
              <select name="status_id" class="form-select form-select-sm" required>
                <option value="">-- Pilih Status --</option>
                <?php if(!empty($list_status)): foreach ($list_status as $st): ?>
                  <option value="<?= $st['id']; ?>"><?= htmlspecialchars($st['nama']); ?></option>
                <?php endforeach; endif; ?>
              </select>
            </div>

            <!-- Serial & Hostname -->
            <div class="col-md-6">
              <label class="form-label" style="font-size:0.85rem;">Serial Number</label>
              <input type="text" name="serial_number" class="form-control form-control-sm" placeholder="S/N Perangkat">
            </div>
            <div class="col-md-6">
              <label class="form-label" style="font-size:0.85rem;">Hostname</label>
              <input type="text" name="hostname" class="form-control form-control-sm" placeholder="Contoh: PC-LOGISTIK-01">
            </div>

            <!-- Jaringan (IP & MAC) -->
            <div class="col-md-6">
              <label class="form-label" style="font-size:0.85rem;">IP Address</label>
              <input type="text" name="ip_address" class="form-control form-control-sm" placeholder="Contoh: 192.168.1.50">
            </div>
            <div class="col-md-6">
              <label class="form-label" style="font-size:0.85rem;">MAC Address</label>
              <input type="text" name="mac_address" class="form-control form-control-sm" placeholder="Contoh: AA:BB:CC:DD:EE:FF">
            </div>

            <!-- Tanggal Beli & Garansi -->
            <div class="col-md-6">
              <label class="form-label" style="font-size:0.85rem;">Tanggal Beli</label>
              <input type="date" name="tanggal_beli" class="form-control form-control-sm">
            </div>
            <div class="col-md-6">
              <label class="form-label" style="font-size:0.85rem;">Garansi Hingga</label>
              <input type="date" name="garansi" class="form-control form-control-sm">
            </div>

            <!-- Media Input Kamera -->
            <div class="col-12">
              <label class="form-label fw-bold text-secondary mb-1" style="font-size:0.85rem;">Foto Asset (Ambil via Webcam / Pilih Berkas)</label>
              <div class="row g-2">
                <div class="col-md-6">
                  <div class="input-group input-group-sm mb-2">
                    <input type="file" name="foto" id="input-file-foto" class="form-control" accept="image/*">
                    <button class="btn btn-primary" type="button" id="btn-aktifkan-webcam" title="Aktifkan Kamera Depan">
                      <i class="bi bi-camera-video-fill me-1"></i> Buka Kamera
                    </button>
                  </div>
                  <input type="hidden" name="foto_webcam" id="foto-base64">
                  <div id="webcam-container" class="d-none border rounded p-1 bg-dark text-center position-relative">
                    <video id="webcam-video" autoplay playsinline class="w-100 rounded" style="max-height: 240px; transform: scaleX(-1); object-fit: cover;"></video>
                    <canvas id="webcam-canvas" class="d-none"></canvas>
                    <div class="mt-2 mb-1">
                      <button type="button" class="btn btn-sm btn-warning fw-semibold text-dark w-100" id="btn-jepret-foto">
                        <i class="bi bi-camera-fill me-1"></i> Ambil Gambar (Snap)
                      </button>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 d-flex align-items-center justify-content-center border rounded bg-light" style="min-height: 150px; max-height: 295px;">
                  <div class="text-center p-2">
                    <img id="pratinjau-hasil-foto" src="uploads/default.jpg" class="img-fluid rounded shadow-sm d-none" style="max-height: 200px; object-fit: contain;">
                    <span id="teks-panduan-pratinjau" class="text-muted" style="font-size: 0.85rem;"><i class="bi bi-image me-1"></i> Belum ada gambar dipilih / diambil</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label" style="font-size:0.85rem;">Manual Book (PDF)</label>
              <input type="file" name="manual_book" class="form-control form-control-sm" accept=".pdf">
            </div>

            <!-- Spesifikasi -->
            <div class="col-12">
              <label class="form-label" style="font-size:0.85rem;">Spesifikasi Lengkap / Catatan Tambahan</label>
              <textarea name="spesifikasi" class="form-control form-control-sm" rows="3" placeholder="Tulis spesifikasi detail hardware/software di sini..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-primary">Simpan Asset</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL EDIT ASSET (SUDAH DISESUAIKAN ID-NYA AGAR JAVASCRIPT BERFUNGSI)    -->
<!-- ========================================================================= -->
<div class="modal fade" id="editAssetModal" tabindex="-1" aria-labelledby="editAssetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title" id="editAssetModalLabel"><i class="bi bi-pencil-square me-2"></i> Ubah Data Asset</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="proses_asset.php?action=update" method="POST" enctype="multipart/form-data">
        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
          <div class="row g-3">
            
            <!-- Kode & Nama -->
            <div class="col-md-6">
              <label class="form-label font-monospace" style="font-size:0.85rem;">Kode Asset <span class="text-danger">*</span></label>
              <input type="text" name="kode_asset" id="edit-kode" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" style="font-size:0.85rem;">Nama Asset <span class="text-danger">*</span></label>
              <input type="text" name="nama" id="edit-nama" class="form-control form-control-sm" required>
            </div>

            <!-- Dropdown Relasi Otomatis dengan ID pencocokan JavaScript -->
            <div class="col-md-3">
              <label class="form-label" style="font-size:0.85rem;">Kategori <span class="text-danger">*</span></label>
              <select name="kategori_id" id="edit-kategori" class="form-select form-select-sm" required>
                <option value="">-- Pilih Kategori --</option>
                <?php if(!empty($list_kategori)): foreach ($list_kategori as $kat): ?>
                  <option value="<?= $kat['id']; ?>"><?= htmlspecialchars($kat['nama']); ?></option>
                <?php endforeach; endif; ?>
              </select>
            </div>
            
            <div class="col-md-3">
              <label class="form-label" style="font-size:0.85rem;">Brand <span class="text-danger">*</span></label>
              <select name="brand_id" id="edit-brand" class="form-select form-select-sm" required>
                <option value="">-- Pilih Brand --</option>
                <?php if(!empty($list_brand)): foreach ($list_brand as $brd): ?>
                  <option value="<?= $brd['id']; ?>"><?= htmlspecialchars($brd['nama']); ?></option>
                <?php endforeach; endif; ?>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label" style="font-size:0.85rem;">Ruangan <span class="text-danger">*</span></label>
              <select name="room_id" id="edit-room" class="form-select form-select-sm" required>
                <option value="">-- Pilih Ruangan --</option>
                <?php if(!empty($list_ruangan)): foreach ($list_ruangan as $rm): ?>
                  <option value="<?= $rm['id']; ?>"><?= htmlspecialchars($rm['nama']); ?></option>
                <?php endforeach; endif; ?>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label" style="font-size:0.85rem;">Status <span class="text-danger">*</span></label>
              <select name="status_id" id="edit-status" class="form-select form-select-sm" required>
                <option value="">-- Pilih Status --</option>
                <?php if(!empty($list_status)): foreach ($list_status as $st): ?>
                  <option value="<?= $st['id']; ?>"><?= htmlspecialchars($st['nama']); ?></option>
                <?php endforeach; endif; ?>
              </select>
            </div>

            <!-- Serial & Hostname -->
            <div class="col-md-6">
              <label class="form-label" style="font-size:0.85rem;">Serial Number</label>
              <input type="text" name="serial_number" id="edit-serial" class="form-control form-control-sm">
            </div>
            <div class="col-md-6">
              <label class="form-label" style="font-size:0.85rem;">Hostname</label>
              <input type="text" name="hostname" id="edit-hostname" class="form-control form-control-sm">
            </div>

            <!-- Jaringan (IP & MAC) -->
            <div class="col-md-6">
              <label class="form-label" style="font-size:0.85rem;">IP Address</label>
              <input type="text" name="ip_address" id="edit-ip" class="form-control form-control-sm">
            </div>
            <div class="col-md-6">
              <label class="form-label" style="font-size:0.85rem;">MAC Address</label>
              <input type="text" name="mac_address" id="edit-mac" class="form-control form-control-sm">
            </div>

            <!-- Tanggal Beli & Garansi -->
            <div class="col-md-6">
              <label class="form-label" style="font-size:0.85rem;">Tanggal Beli</label>
              <input type="date" name="tanggal_beli" id="edit-tgl" class="form-control form-control-sm">
            </div>
            <div class="col-md-6">
              <label class="form-label" style="font-size:0.85rem;">Garansi Hingga</label>
              <input type="date" name="garansi" id="edit-garansi" class="form-control form-control-sm">
            </div>

<!-- KOTAK MEDIA FOTO MODAL EDIT (FIXED PENAMPUNG DATA) -->
<div class="col-12">
  <label class="form-label fw-bold text-secondary mb-1" style="font-size:0.85rem;">Ganti Foto Asset (Ambil via Webcam / Pilih Berkas)</label>
  <div class="row g-2">
    <div class="col-md-6">
      <div class="input-group input-group-sm mb-2">
        <!-- Input File Explorer Utama (name="foto" harus ada) -->
        <input type="file" name="foto" id="edit-input-file-foto" class="form-control" accept="image/*">
        <button class="btn btn-primary" type="button" id="edit-btn-aktifkan-webcam" title="Aktifkan Kamera">
          <i class="bi bi-camera-video-fill me-1"></i> Buka Kamera
        </button>
      </div>
      
      <!-- FIX UTAMA: Input tersembunyi wajib ditambahkan agar data string biner webcam terkirim saat form disubmit -->
      <input type="hidden" name="foto_webcam" id="edit-foto-base64">
      
      <div id="edit-webcam-container" class="d-none border rounded p-1 bg-dark text-center position-relative">
        <video id="edit-webcam-video" autoplay playsinline class="w-100 rounded" style="max-height: 240px; transform: scaleX(-1); object-fit: cover;"></video>
        <canvas id="edit-webcam-canvas" class="d-none"></canvas>
        <div class="mt-2 mb-1">
          <button type="button" class="btn btn-sm btn-warning fw-semibold text-dark w-100" id="edit-btn-jepret-foto">
            <i class="bi bi-camera-fill me-1"></i> Ambil Gambar (Snap)
          </button>
        </div>
      </div>
    </div>

    <div class="col-md-6 d-flex align-items-center justify-content-center border rounded bg-light" style="min-height: 150px; max-height: 295px;">
      <div class="text-center p-2">
        <img id="edit-pratinjau-hasil-foto" src="uploads/default.jpg" class="img-fluid rounded shadow-sm d-none" style="max-height: 200px; object-fit: contain;">
        <span id="edit-teks-panduan-pratinjau" class="text-muted" style="font-size: 0.85rem;"><i class="bi bi-image me-1"></i> Belum ada gambar baru dipilih / diambil</span>
      </div>
    </div>
  </div>
</div>
            <!-- Ganti Manal Book -->
            <div class="col-md-6">
              <label class="form-label" style="font-size:0.85rem;">Ganti Manual Book (PDF) <small class="text-muted">(Biarkan kosong jika tidak diubah)</small></label>
              <input type="file" name="manual_book" class="form-control form-control-sm" accept=".pdf">
            </div>

            <!-- Spesifikasi -->
            <div class="col-12">
              <label class="form-label" style="font-size:0.85rem;">Spesifikasi Lengkap / Catatan Tambahan</label>
              <textarea name="spesifikasi" id="edit-spek" class="form-control form-control-sm" rows="3"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-warning fw-bold">Update Asset</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ==========================================
    // LOGIKA 1: MODAL POP-UP DETAIL ASSET (SUDAH DIPERBAIKI)
    // ==========================================
    const detailButtons = document.querySelectorAll('.btn-detail');
    
    detailButtons.forEach(button => {
        button.addEventListener('click', function () {
            const kode = this.getAttribute('data-kode');
            const nama = this.getAttribute('data-nama');
            const serial = this.getAttribute('data-serial') || '-';
            const hostname = this.getAttribute('data-hostname') || '-';
            const ip = this.getAttribute('data-ip') || '-';
            const mac = this.getAttribute('data-mac') || '-';
            const tgl = this.getAttribute('data-tgl') || '-';
            const garansi = this.getAttribute('data-garansi') || '-';
            const foto = this.getAttribute('data-foto');
            const manual = this.getAttribute('data-manual');
            const spek = this.getAttribute('data-spek') || 'Tidak ada spesifikasi tambahan.';

            document.getElementById('detail-kode').innerText = kode;
            document.getElementById('detail-nama').innerText = nama;
            document.getElementById('detail-serial').innerText = serial;
            document.getElementById('detail-hostname').innerText = hostname;
            document.getElementById('detail-ip').innerText = ip;
            document.getElementById('detail-mac').innerText = mac;
            document.getElementById('detail-tgl').innerText = tgl;
            document.getElementById('detail-garansi').innerText = garansi;
            document.getElementById('detail-spek').innerText = spek;

            const imgElement = document.getElementById('detail-foto');
            // PERBAIKAN: Ditambah pengaman jika data foto null, kosong, atau string "null"
            if (foto && foto.trim() !== '' && foto !== 'default.jpg' && foto !== '-' && foto !== 'null') {
                imgElement.src = 'uploads/' + foto;
            } else {
                imgElement.src = 'uploads/default.jpg';
            }

            const manualLink = document.getElementById('detail-manual-link');
            const manualText = document.getElementById('detail-manual-text');
            if (manual && manual !== '-') {
                manualLink.href = 'uploads/' + manual;
                manualLink.classList.remove('d-none');
                manualText.classList.add('d-none');
            } else {
                manualLink.classList.add('d-none');
                manualText.innerText = 'Tidak tersedia';
                manualText.classList.remove('d-none');
            }
        });
    });

    // ==========================================
    // LOGIKA 2: MODAL POP-UP EDIT ASSET (UPDATE)
    // ==========================================
    const editButtons = document.querySelectorAll('.btn-edit');
    
    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const formEdit = document.querySelector('#editAssetModal form');
            if (formEdit) {
                formEdit.action = 'proses_asset.php?action=update&id=' + id;
            }

            document.getElementById('edit-kategori').value = this.getAttribute('data-kategori');
            document.getElementById('edit-brand').value    = this.getAttribute('data-brand');
            document.getElementById('edit-room').value     = this.getAttribute('data-room');
            document.getElementById('edit-status').value   = this.getAttribute('data-status');

            document.getElementById('edit-kode').value     = this.getAttribute('data-kode');
            document.getElementById('edit-nama').value     = this.getAttribute('data-nama');
            document.getElementById('edit-serial').value   = this.getAttribute('data-serial') !== '-' ? this.getAttribute('data-serial') : '';
            document.getElementById('edit-hostname').value = this.getAttribute('data-hostname') !== '-' ? this.getAttribute('data-hostname') : '';
            document.getElementById('edit-ip').value       = this.getAttribute('data-ip') !== '-' ? this.getAttribute('data-ip') : '';
            document.getElementById('edit-mac').value      = this.getAttribute('data-mac') !== '-' ? this.getAttribute('data-mac') : '';
            document.getElementById('edit-tgl').value      = this.getAttribute('data-tgl') !== '-' ? this.getAttribute('data-tgl') : '';
            document.getElementById('edit-garansi').value  = this.getAttribute('data-garansi') !== '-' ? this.getAttribute('data-garansi') : '';
            document.getElementById('edit-spek').value     = this.getAttribute('data-spek') !== 'Tidak ada spesifikasi tambahan.' ? this.getAttribute('data-spek') : '';
        });
    });

    // ==========================================
    // LOGIKA 3: PENGENDALI WEBCAM MEDIA DEVICES
    // ==========================================
    const btnBukaWebcam  = document.getElementById('btn-aktifkan-webcam');
    const btnJepret      = document.getElementById('btn-jepret-foto');
    const containerCam   = document.getElementById('webcam-container');
    const video          = document.getElementById('webcam-video');
    const canvas         = document.getElementById('webcam-canvas');
    const inputBase64    = document.getElementById('foto-base64');
    const imgPreview     = document.getElementById('pratinjau-hasil-foto');
    const teksPanduan    = document.getElementById('teks-panduan-pratinjau');
    const inputFile      = document.getElementById('input-file-foto');

    let streamLokal = null;

    if (btnBukaWebcam) {
        btnBukaWebcam.addEventListener('click', async function () {
            if (streamLokal) {
                matikanWebcam();
                return;
            }

            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: "user" },
                    audio: false
                });
                
                streamLokal = stream;
                video.srcObject = stream;
                containerCam.classList.remove('d-none');
                btnBukaWebcam.innerHTML = '<i class="bi bi-camera-video-off-fill me-1"></i> Tutup Kamera';
                btnBukaWebcam.classList.replace('btn-primary', 'btn-danger');
                if (inputFile) inputFile.value = ''; 
            } catch (err) {
                alert("Gagal mengakses web kamera: " + err.message + "\nPastikan izin kamera diijinkan di browser.");
            }
        });
    }

    if (btnJepret) {
        btnJepret.addEventListener('click', function () {
            if (!streamLokal) return;

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            const ctx = canvas.getContext('2d');
            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
            
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            const dataGambarBase64 = canvas.toDataURL('image/png');
            
            inputBase64.value = dataGambarBase64;
            imgPreview.src = dataGambarBase64;
            imgPreview.classList.remove('d-none');
            teksPanduan.classList.add('d-none');

            matikanWebcam();
        });
    }

    if (inputFile) {
        inputFile.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                matikanWebcam(); 
                inputBase64.value = ''; 

                const reader = new FileReader();
                reader.onload = function(e) {
                    imgPreview.src = e.target.result;
                    imgPreview.classList.remove('d-none');
                    teksPanduan.classList.add('d-none');
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    function matikanWebcam() {
        if (streamLokal) {
            streamLokal.getTracks().forEach(track => track.stop());
            streamLokal = null;
        }
        if (video) video.srcObject = null;
        if (containerCam) containerCam.classList.add('d-none');
        if (btnBukaWebcam) {
            btnBukaWebcam.innerHTML = '<i class="bi bi-camera-video-fill me-1"></i> Buka Kamera';
            btnBukaWebcam.classList.replace('btn-danger', 'btn-primary');
        }
    }
});

    // ==========================================
    // LOGIKA 4: PENGENDALI WEBCAM DI MODAL EDIT
    // ==========================================
    const editBtnBukaWebcam  = document.getElementById('edit-btn-aktifkan-webcam');
    const editBtnJepret      = document.getElementById('edit-btn-jepret-foto');
    const editContainerCam   = document.getElementById('edit-webcam-container');
    const editVideo          = document.getElementById('edit-webcam-video');
    const editCanvas         = document.getElementById('edit-webcam-canvas');
    const editInputBase64    = document.getElementById('edit-foto-base64');
    const editImgPreview     = document.getElementById('edit-pratinjau-hasil-foto');
    const editTeksPanduan    = document.getElementById('edit-teks-panduan-pratinjau');
    const editInputFile      = document.getElementById('edit-input-file-foto');

    let editStreamLokal = null;

    if (editBtnBukaWebcam) {
        editBtnBukaWebcam.addEventListener('click', async function () {
            if (editStreamLokal) {
                matikanEditWebcam();
                return;
            }
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: "user" },
                    audio: false
                });
                editStreamLokal = stream;
                editVideo.srcObject = stream;
                editContainerCam.classList.remove('d-none');
                editBtnBukaWebcam.innerHTML = '<i class="bi bi-camera-video-off-fill me-1"></i> Tutup Kamera';
                editBtnBukaWebcam.classList.replace('btn-primary', 'btn-danger');
                if (editInputFile) editInputFile.value = ''; 
            } catch (err) {
                alert("Gagal mengakses kamera di modal edit: " + err.message);
            }
        });
    }

    if (editBtnJepret) {
        editBtnJepret.addEventListener('click', function () {
            if (!editStreamLokal) return;
            editCanvas.width = editVideo.videoWidth;
            editCanvas.height = editVideo.videoHeight;
            const ctx = editCanvas.getContext('2d');
            ctx.translate(editCanvas.width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(editVideo, 0, 0, editCanvas.width, editCanvas.height);
            
            const base64Data = editCanvas.toDataURL('image/png');
            editInputBase64.value = base64Data;
            editImgPreview.src = base64Data;
            editImgPreview.classList.remove('d-none');
            editTeksPanduan.classList.add('d-none');
            matikanEditWebcam();
        });
    }

    if (editInputFile) {
        editInputFile.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                matikanEditWebcam(); 
                editInputBase64.value = ''; 
                const reader = new FileReader();
                reader.onload = function(e) {
                    editImgPreview.src = e.target.result;
                    editImgPreview.classList.remove('d-none');
                    editTeksPanduan.classList.add('d-none');
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    function matikanEditWebcam() {
        if (editStreamLokal) {
            editStreamLokal.getTracks().forEach(track => track.stop());
            editStreamLokal = null;
        }
        if (editVideo) editVideo.srcObject = null;
        if (editContainerCam) editContainerCam.classList.add('d-none');
        if (editBtnBukaWebcam) {
            editBtnBukaWebcam.innerHTML = '<i class="bi bi-camera-video-fill me-1"></i> Buka Kamera';
            editBtnBukaWebcam.classList.replace('btn-danger', 'btn-primary');
        }
    }

</script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
