<?php
require_once __DIR__ . '/auth.php';
require_login();

// Menggunakan koneksi database terpusat proyek Anda
require_once __DIR__ . '/db.php'; 

// Konfigurasi batasan data per halaman
$perPage = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Menangkap kata kunci dari form pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // 1. Ambil daftar ID saja dari tabel assets untuk dropdown modal
    $stmtAllAssets = $conn->prepare("SELECT id FROM assets ORDER BY id ASC");
    $stmtAllAssets->execute();
    $assetsList = $stmtAllAssets->fetchAll(PDO::FETCH_ASSOC);

    // 2. Siapkan klausa kondisi SQL jika user melakukan pencarian
    $sqlSearch = "";
    if ($search !== "") {
        $sqlSearch = " WHERE os LIKE :search 
                          OR cpu LIKE :search 
                          OR ram LIKE :search 
                          OR storage LIKE :search 
                          OR rack LIKE :search 
                          OR fungsi LIKE :search";
    }

    // 3. Hitung total data hasil filter pencarian untuk membuat link halaman (pagination)
    $stmtCount = $conn->prepare("SELECT COUNT(*) FROM servers" . $sqlSearch);
    if ($search !== "") {
        $stmtCount->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    $stmtCount->execute();
    $totalRows = $stmtCount->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);

    // 4. Ambil data server secara mandiri tanpa LEFT JOIN kolom nama_asset yang error
    $sqlServers = "SELECT * FROM servers" . $sqlSearch . " LIMIT :limit OFFSET :offset";

    $stmtServers = $conn->prepare($sqlServers);
    if ($search !== "") {
        $stmtServers->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    $stmtServers->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmtServers->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtServers->execute();
    $serversData = $stmtServers->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Koneksi gagal atau query bermasalah: " . $e->getMessage();
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

<!-- AREA UTAMA KONTEN (Gunakan pembungkus ini agar susunan halaman tidak bergeser tertimpa sidebar) -->
    <main class="col-md-8 ms-sm-auto col-lg-9 px-md-4 pt-4 offset-md-4 offset-lg-3">

      <!-- HEADER HALAMAN & TOMBOL AKSI -->
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-hdd-network me-2"></i> Manajemen Server</h1>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Fleksibel Otomatis: Hanya tampil jika Role memiliki izin Create ('C') di file ini -->
            <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'C', $userRole)): ?>
            <button class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddServer">
                <i class="bi bi-plus-lg me-1"></i> Tambah Server
            </button>
            <?php endif; ?>

            <!-- Tombol Menu khusus tampilan Mobile jika Sidebar tertutup -->
            <button class="btn btn-sm btn-outline-secondary d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
              <i class="bi bi-list"></i> Menu
            </button>
        </div>
      </div>

      <!-- NOTIFIKASI FLASH ALERT (OTOMATIS SINKRON DENGAN BACKEND PHP) -->
      <?php if (isset($_SESSION['msg_success'])): ?>
          <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
              <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i> 
                <div><?php echo $_SESSION['msg_success']; unset($_SESSION['msg_success']); ?></div>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
      <?php endif; ?>

      <?php if (isset($_SESSION['msg_error'])): ?>
          <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
              <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i> 
                <div><?php echo $_SESSION['msg_error']; unset($_SESSION['msg_error']); ?></div>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
      <?php endif; ?>

      <!-- KONTROL BAR: PENCARIAN & TOMBOL TAMBAH DATA -->
      <div class="row g-3 mb-4 align-items-center">
        <div class="col-12 col-md-6 col-lg-4">
          <form method="GET" action="" class="input-group shadow-sm">
            <input type="text" name="search" class="form-control" placeholder="Cari OS, CPU, atau Fungsi..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
            <?php if(!empty($search)): ?>
              <a href="server.php" class="btn btn-outline-secondary" title="Reset Pencarian"><i class="bi bi-x-circle"></i></a>
            <?php endif; ?>
          </form>
        </div>
        
        <!-- Fleksibel Otomatis: Hanya tampil jika Role memiliki izin Create ('C') di file ini -->
        <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'C', $userRole)): ?>
        <div class="col-12 col-md-6 col-lg-8 text-md-end">
          <button class="btn btn-success shadow-sm" type="button" data-bs-toggle="modal" data-bs-target="#modalTambahServer">
            <i class="bi bi-plus-lg me-1"></i> Tambah Server Baru
          </button>
        </div>
        <?php endif; ?>
        
      </div>

<!-- TABEL DATA SERVER (SUDAH DIRAPIKAN JARAK KOLOMNYA) -->
<div class="card shadow-sm border-0 rounded-3 mb-4">
  <div class="card-body p-0">
    <div class="table-responsive">
      <!-- Menambahkan class table-bordered (opsional) untuk garis pembatas tipis antar kolom agar makin rapi -->
      <table class="table table-hover table-striped align-middle mb-0 text-nowrap table-bordered">
        <thead class="table-dark">
          <tr>
            <th scope="col" class="px-3 text-center" style="width: 50px;">ID</th>
            <th scope="col" class="px-3" style="min-width: 120px;">Asset ID</th>
            <th scope="col" class="px-3" style="min-width: 180px;">OS</th>
            <th scope="col" class="px-3" style="min-width: 220px;">CPU</th>
            <th scope="col" class="px-3" style="min-width: 120px;">RAM</th>
            <th scope="col" class="px-3" style="min-width: 140px;">Storage</th>
            <th scope="col" class="px-3" style="min-width: 110px;">Rack</th>
            <th scope="col" class="px-3" style="min-width: 250px;">Fungsi</th>
            <th scope="col" class="px-3 text-center" style="width: 100px;">Status</th>
            
            <!-- Fleksibel Otomatis: Hanya tampilkan kolom header Aksi jika user bukan Viewer -->
            <?php if ($userRole !== 'Viewer'): ?>
            <th scope="col" class="px-3 text-center" style="width: 100px;">Aksi</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($serversData)): ?>
            <?php foreach ($serversData as $server): ?>
              <tr>
                <td class="px-3 text-center fw-bold"><?php echo htmlspecialchars($server['id']); ?></td>
                <td class="px-3">
                  <!-- Memastikan badge tidak terlalu mepet dengan teks kolom lain -->
                  <span class="badge bg-secondary px-2 py-1.5">Asset ID: <?php echo htmlspecialchars($server['asset_id'] ?? '-'); ?></span>
                </td>
                <td class="px-3"><?php echo htmlspecialchars($server['os'] ?? '-'); ?></td>
                <td class="px-3 fw-mono text-secondary" style="font-size: 0.9rem;"><?php echo htmlspecialchars($server['cpu'] ?? '-'); ?></td>
                <td class="px-3"><?php echo htmlspecialchars($server['ram'] ?? '-'); ?></td>
                <td class="px-3"><?php echo htmlspecialchars($server['storage'] ?? '-'); ?></td>
                <td class="px-3"><i class="bi bi-layers text-muted me-1"></i> <?php echo htmlspecialchars($server['rack'] ?? '-'); ?></td>
                <td class="px-3 text-wrap-normal" style="max-width: 300px; white-space: normal;"><?php echo htmlspecialchars($server['fungsi'] ?? '-'); ?></td>
                <td class="px-3 text-center">
                  <?php if ((int)$server['status'] === 1): ?>
                    <span class="badge bg-success rounded-pill px-3"><i class="bi bi-check-circle me-1"></i> Aktif</span>
                  <?php else: ?>
                    <span class="badge bg-danger rounded-pill px-3"><i class="bi bi-x-circle me-1"></i> Non-Aktif</span>
                  <?php endif; ?>
                </td>
                
                <!-- Fleksibel Otomatis: Blok kolom aksi disembunyikan total dari Viewer (X) -->
                <?php if ($userRole !== 'Viewer'): ?>
                <td class="px-3 text-center">
                  <div class="btn-group" role="group">
                    
                    <!-- Fleksibel Otomatis: Cek Akses Update ('U') untuk tombol Edit Kuning -->
                    <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'U', $userRole)): ?>
                    <button class="btn btn-sm btn-outline-warning btn-edit-server" title="Ubah Data Server"
                            data-id="<?php echo $server['id']; ?>"
                            data-asset="<?php echo htmlspecialchars($server['asset_id'] ?? ''); ?>"
                            data-os="<?php echo htmlspecialchars($server['os'] ?? ''); ?>"
                            data-cpu="<?php echo htmlspecialchars($server['cpu'] ?? ''); ?>"
                            data-ram="<?php echo htmlspecialchars($server['ram'] ?? ''); ?>"
                            data-storage="<?php echo htmlspecialchars($server['storage'] ?? ''); ?>"
                            data-rack="<?php echo htmlspecialchars($server['rack'] ?? ''); ?>"
                            data-fungsi="<?php echo htmlspecialchars($server['fungsi'] ?? ''); ?>"
                            data-status="<?php echo $server['status']; ?>">
                      <i class="bi bi-pencil-square"></i>
                    </button>
                    <?php endif; ?>
                    
                    <!-- Fleksibel Otomatis: Cek Akses Delete ('D') untuk tombol Hapus Merah -->
                    <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'D', $userRole)): ?>
                    <button class="btn btn-sm btn-outline-danger btn-delete-server" title="Hapus Server"
                            data-id="<?php echo $server['id']; ?>">
                      <i class="bi bi-trash"></i>
                    </button>
                    <?php endif; ?>
                    
                  </div>
                </td>
                <?php endif; ?>
                
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="<?= ($userRole === 'Viewer') ? '9' : '10'; ?>" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i> Data server tidak ditemukan atau tabel kosong.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

      <!-- NAVIGASI HALAMAN (PAGINATION) -->
      <?php if (isset($totalPages) && $totalPages > 1): ?>
        <nav aria-label="Navigasi Halaman" class="mb-5">
          <ul class="pagination justify-content-center">
            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
              <a class="page-link shadow-sm" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search ?? ''); ?>" tabindex="-1">Sebelumnya</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                <a class="page-link shadow-sm" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search ?? ''); ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
              <a class="page-link shadow-sm" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search ?? ''); ?>">Selanjutnya</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    </main>

  </div>
</div>

<!-- ==========================================
      MODAL POPUP: FORM TAMBAH DATA SERVER
=========================================== -->
<div class="modal fade" id="modalTambahServer" tabindex="-1" aria-labelledby="modalTambahServerLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0">
      
      <!-- Header Modal -->
      <div class="modal-header bg-dark text-white py-2">
        <h5 class="modal-title" id="modalTambahServerLabel">
          <i class="bi bi-hdd-rack me-2 text-warning"></i> Tambah Server Baru
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <!-- Form Input diarahkan ke proses_server.php -->
      <form action="proses_server.php" method="POST">
        <input type="hidden" name="action" value="tambah_server">
        
        <div class="modal-body py-2">
          <div class="row g-2">
            
            <!-- Baris 1: Dropdown Pilihan Asset & Operating System -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Asset ID (Hubungan Aset)</label>
<select name="asset_id" class="form-select form-select-sm">
  <option value="">-- Hubungkan ke Aset --</option>
  <?php if (!empty($assetsList)): ?>
    <?php foreach ($assetsList as $assetOption): ?>
      <option value="<?php echo $assetOption['id']; ?>">
        Asset ID: <?php echo $assetOption['id']; ?>
      </option>
    <?php endforeach; ?>
  <?php endif; ?>
</select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Operating System (OS)</label>
              <input type="text" name="os" class="form-control form-control-sm" placeholder="Contoh: Ubuntu Server 22.04" required>
            </div>
            
            <!-- Baris 2: CPU & RAM -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">CPU</label>
              <input type="text" name="cpu" class="form-control form-control-sm" placeholder="Intel Xeon..." required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">RAM</label>
              <input type="text" name="ram" class="form-control form-control-sm" placeholder="16 GB" required>
            </div>
            
            <!-- Baris 3: Storage & Nomor Rack -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Storage</label>
              <input type="text" name="storage" class="form-control form-control-sm" placeholder="500 GB SSD" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Rack No.</label>
              <input type="text" name="rack" class="form-control form-control-sm" placeholder="Rack A-01">
            </div>
            
            <!-- Baris 4: Fungsi Server & Status Server -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Fungsi Server</label>
              <input type="text" name="fungsi" class="form-control form-control-sm" placeholder="Web Server, Database, dll...">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Status Server</label>
              <select name="status" class="form-select form-select-sm">
                <option value="1">Aktif</option>
                <option value="0">Non-Aktif</option>
              </select>
            </div>

          </div>
        </div>
        
        <!-- Footer Modal -->
        <div class="modal-footer bg-light py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-success px-4">Simpan Data</button>
        </div>
      </form>
      
    </div>
  </div>
</div>

<!-- ==========================================
      MODAL POPUP: FORM EDIT DATA SERVER
=========================================== -->
<div class="modal fade" id="modalEditServer" tabindex="-1" aria-labelledby="modalEditServerLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0">
      
      <!-- Header Modal -->
      <div class="modal-header bg-dark text-white py-2">
        <h5 class="modal-title" id="modalEditServerLabel">
          <i class="bi bi-pencil-square me-2 text-warning"></i> Edit Data Server
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <!-- Form Input diarahkan ke proses_server.php -->
      <form action="proses_server.php" method="POST">
        <!-- Input Hidden Penanda Aksi & ID Primary Key -->
        <input type="hidden" name="action" value="edit_server">
        <input type="hidden" name="id" id="edit-id">
        
        <div class="modal-body py-2">
          <div class="row g-2">
            
            <!-- Baris 1: Dropdown Pilihan Asset & Operating System -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Asset ID (Hubungan Aset)</label>
              <select name="asset_id" id="edit-asset" class="form-select form-select-sm">
                <option value="">-- Hubungkan ke Aset (Opsional) --</option>
                <?php if (!empty($assetsList)): ?>
                  <?php foreach ($assetsList as $assetOption): ?>
                    <option value="<?php echo $assetOption['id']; ?>">
                      [ID: <?php echo $assetOption['id']; ?>] <?php echo htmlspecialchars($assetOption['nama_asset']); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Operating System (OS)</label>
              <input type="text" name="os" id="edit-os" class="form-control form-control-sm" required>
            </div>
            
            <!-- Baris 2: CPU & RAM -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">CPU</label>
              <input type="text" name="cpu" id="edit-cpu" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">RAM</label>
              <input type="text" name="ram" id="edit-ram" class="form-control form-control-sm" required>
            </div>
            
            <!-- Baris 3: Storage & Nomor Rack -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Storage</label>
              <input type="text" name="storage" id="edit-storage" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Rack No.</label>
              <input type="text" name="rack" id="edit-rack" class="form-control form-control-sm">
            </div>
            
            <!-- Baris 4: Fungsi Server & Status Server -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Fungsi Server</label>
              <input type="text" name="fungsi" id="edit-fungsi" class="form-control form-control-sm">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Status Server</label>
              <select name="status" id="edit-status" class="form-select form-select-sm">
                <option value="1">Aktif</option>
                <option value="0">Non-Aktif</option>
              </select>
            </div>

          </div>
        </div>
        
        <!-- Footer Modal -->
        <div class="modal-footer bg-light py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-primary px-4">Simpan Perubahan</button>
        </div>
      </form>
      
    </div>
  </div>
</div>

<!-- SCRIPT JS PENDUKUNG BOOTSTRAP & OPERASI CRUD -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    
    // ====================================================
    // 1. OTOMATIS MENUTUP NOTIFIKASI ALERTS (Setelah 5 Detik)
    // ====================================================
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // ====================================================
    // 2. PARSING DATA TABEL KE DALAM MODAL FORM EDIT
    // ====================================================
    const editButtons = document.querySelectorAll('.btn-edit-server');
    const modalEditInstance = new bootstrap.Modal(document.getElementById('modalEditServer'));

    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            // Mengisi nilai input modal berdasarkan atribut data tombol tabel
            document.getElementById('edit-id').value = this.getAttribute('data-id');
            document.getElementById('edit-asset').value = this.getAttribute('data-asset');
            document.getElementById('edit-os').value = this.getAttribute('data-os');
            document.getElementById('edit-cpu').value = this.getAttribute('data-cpu');
            document.getElementById('edit-ram').value = this.getAttribute('data-ram');
            document.getElementById('edit-storage').value = this.getAttribute('data-storage');
            document.getElementById('edit-rack').value = this.getAttribute('data-rack');
            document.getElementById('edit-fungsi').value = this.getAttribute('data-fungsi');
            document.getElementById('edit-status').value = this.getAttribute('data-status');

            // Membuka jendela pop-up modal edit secara programatis
            modalEditInstance.show();
        });
    });

    // ====================================================
    // 3. KONFIRMASI AMAN SEBELUM MENGHAPUS DATA SERVER
    // ====================================================
    const deleteButtons = document.querySelectorAll('.btn-delete-server');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            const serverId = this.getAttribute('data-id');
            // Menampilkan dialog konfirmasi bawaan browser untuk mencegah ketidaksengajaan
            if (confirm(`Apakah Anda yakin ingin menghapus data server dengan ID ${serverId}? Data yang terhapus tidak dapat dikembalikan.`)) {
                // Alihkan ke backend proses_server.php dengan parameter hapus
                window.location.href = `proses_server.php?action=hapus_server&id=${serverId}`;
            }
        });
    });

});
</script>

    <?php include 'footer-admin.php'; ?>
