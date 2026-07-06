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

    // TRIGGER LOG OTOMATIS GLOBAL: Mencatat log kunjungan halaman
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['action'])) {
        write_log($conn, "Membuka halaman Password Vault", "password_vaults", null);
    }

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
            $sukses = $stmt->execute([
                ':kategori_id' => $kategori_id,
                ':nama'        => $nama,
                ':url'         => $url,
                ':ip'          => $ip,
                ':username'    => $username_v,
                ':password'    => $password_v,
                ':tipe'        => $tipe,
                ':catatan'     => $catatan
            ]);
            
            // AMBIL ID BARU DAN TULIS LOG AKTIVITAS (CREATE)
            if ($sukses) {
                $new_id = $conn->lastInsertId();
                write_log($conn, "Menambahkan data vault password baru: " . $nama, "password_vaults", $new_id);
            }
            
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
            $sukses_update = $stmt->execute([
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
            
            // TULIS LOG AKTIVITAS (UPDATE)
            if ($sukses_update) {
                write_log($conn, "Mengubah data vault password: " . $nama, "password_vaults", $id);
            }
            
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
            // 1. Ambil nama vault password terlebih dahulu untuk log sebelum datanya terhapus
            $get_info = $conn->prepare("SELECT nama FROM password_vaults WHERE id = :id");
            $get_info->execute([':id' => $id]);
            $nama_vault = $get_info->fetchColumn() ?: 'Unknown';

            // 2. Jalankan query hapus data
            $stmt = $conn->prepare("DELETE FROM password_vaults WHERE id = :id");
            $sukses_delete = $stmt->execute([':id' => $id]);
            
            // TULIS LOG AKTIVITAS (DELETE)
            if ($sukses_delete) {
                write_log($conn, "Menghapus data vault password: " . $nama_vault, "password_vaults", $id);
            }
            
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
    
    <link href="sidebar-admin.css" rel="stylesheet">

    <style>
        /* ==================================================== */
        /* CONFIG LAYOUT DESKTOP / LAPTOP (Lebar Layar >= 768px)*/
        /* ==================================================== */
        @media (min-width: 768px) {
            body {
                display: flex !important;
                overflow: auto !important;
            }
            .offcanvas-md.sidebar-fixed {
                position: fixed !important;
                top: 0;
                left: 0;
                min-height: 100vh !important;
                max-height: 100vh !important;
                width: 280px !important;
                z-index: 1040;
                transform: none !important;
                visibility: visible !important;
                overflow: hidden !important;
            }
            .menu-scroll-container {
                display: block !important;
                max-height: calc(100vh - 160px) !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                scrollbar-width: none;
                -ms-overflow-style: none;
            }
            .menu-scroll-container::-webkit-scrollbar {
                display: none !important;
            }
            /* Menghitung lebar konten di desktop */
            .main-content {
                margin-left: 280px !important;
                width: calc(100% - 280px) !important;
                min-height: 100vh !important;
                display: block !important; /* Kembalikan sifat block di laptop */
            }
            .offcanvas-backdrop, .offcanvas-backdrop.show {
                display: none !important;
                opacity: 0 !important;
                visibility: hidden !important;
                pointer-events: none !important;
            }
        }

        /* ==================================================== */
        /* CONFIG LAYOUT MOBILE / HP (Lebar Layar < 768px)     */
        /* ==================================================== */
        @media (max-width: 767.98px) {
            body {
                overflow: auto !important;
                position: relative !important;
                display: block !important; /* Hancurkan flexbox desktop di HP */
            }
            /* Kunci container induk luar agar di HP melepas kuncian flex */
            .d-md-flex {
                display: block !important; 
            }
            .sidebar-fixed {
                position: fixed !important;
                width: 280px !important;
                height: 100vh !important;
            }
            .menu-scroll-container {
                max-height: none !important;
                overflow-y: visible !important;
            }
            /* SOLUSI UTAMA: Hancurkan pembatasan tinggi pendek di HP */
            .main-content {
                width: 100% !important;
                margin-left: 0 !important;
                min-height: calc(100vh - 70px) !important; /* Ambil sisa tinggi layar HP */
                display: block !important;
                height: auto !important; /* Biarkan memanjang alami mengikuti jumlah data tabel */
            }
            .offcanvas-backdrop.show {
                display: block !important;
                opacity: 0 !important;
                background-color: transparent !important;
                visibility: visible !important;
            }
        }

        /* ==================================================== */
        /* KUSTOMISASI MODEREN MENU STYLE                      */
        /* ==================================================== */
        .sidebar-fixed .nav-link {
            color: #adb5bd !important;
            font-weight: 500;
            padding: 10px 16px !important;
            margin: 2px 0;
            transition: all 0.25s ease-in-out;
            border-left: 4px solid transparent;
        }
        .sidebar-fixed .nav-link:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.06) !important;
            border-left-color: rgba(255, 255, 255, 0.3);
            padding-left: 20px !important;
        }
        .sidebar-fixed .nav-link.active-style {
            color: #ffffff !important;
            background: linear-gradient(90deg, rgba(13, 110, 253, 0.25) 0%, rgba(13, 110, 253, 0.05) 100%) !important;
            border-left: 4px solid #0d6efd !important;
            box-shadow: inset 0 0 8px rgba(13, 110, 253, 0.15);
            font-weight: 600;
        }
        .sidebar-fixed .nav-link i {
            font-size: 1.1rem;
            transition: transform 0.25s ease;
        }
        .sidebar-fixed .nav-link:hover i {
            transform: scale(1.15);
        }
        .sidebar-fixed .nav-link.active-style i {
            color: #0d6efd !important;
        }
    </style>

</head>
<body>

<!-- ==================================================================== -->
<!-- BAGIAN A: TOMBOL PEMICU SIDEBAR (HANYA MUNCUL DI MOBILE / HP)        -->
<!-- ==================================================================== -->
<div class="d-md-none p-3 bg-dark d-flex justify-content-between align-items-center w-100 position-sticky top-0 shadow-sm" style="z-index: 1050;">
    <h5 class="text-warning mb-0 fw-bold"><i class="bi bi-speedometer2 me-2"></i> ITAKMS</h5>
    <button class="btn btn-warning btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarFlexible" aria-controls="sidebarFlexible">
        <i class="bi bi-list fs-5"></i>
    </button>
</div>

<!-- ==================================================================== -->
<!-- BAGIAN B: INDUK KONTEN SIDEBAR RESPONSIVE (FLEKSIBEL HP & DESKTOP)   -->
<!-- ==================================================================== -->
<div class="offcanvas-md offcanvas-start d-flex flex-column sidebar-fixed p-3 text-bg-dark border-end border-secondary" 
     tabindex="-1" id="sidebarFlexible" aria-labelledby="sidebarFlexibleLabel">

  <!-- 1. Header Laci Menu (Hanya Muncul di Layar HP saat Laci Terbuka) -->
  <div class="offcanvas-header border-bottom border-secondary d-md-none">
    <h5 class="offcanvas-title text-warning fw-bold" id="sidebarFlexibleLabel">
        <i class="bi bi-speedometer2 me-2"></i> ITAKMS
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebarFlexible" aria-label="Close"></button>
  </div>
  
  <!-- 2. Judul Utama Navigasi (Hanya Muncul di Desktop/Laptop) -->
  <h4 class="text-center mb-4 text-warning fw-bold pt-2 d-none d-md-block">
      <i class="bi bi-speedometer2 me-2"></i> ITAKMS
  </h4>
  
  <!-- 3. AREA MENU TENGAH (Otomatis & Fleksibel untuk Semua Opsi Berbasis Fungsi PHP) -->
  <?php
  // Ambil nama file yang sedang aktif saat ini secara real-time
  $currentFile = basename($_SERVER['PHP_SELF']);

  // Fungsi pintar untuk otomatis mencetak class 'active-style' jika nama file cocok
  if (!function_exists('checkActiveMenu')) {
      function checkActiveMenu($targetFile, $currentFile) {
          return ($currentFile === $targetFile) ? 'active-style' : '';
      }
  }
  ?>
  
    <?php include __DIR__ . '/sidebar.php'; ?>

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

<?php include 'footer-admin.php'; ?>
