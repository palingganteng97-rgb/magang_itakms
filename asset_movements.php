<?php
// =========================================================================
// LOGIKA BACKEND UTAMA - ASSET MOVEMENTS VIEW WITH SEARCH
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

    // TRIGGER LOG OTOMATIS GLOBAL: Mencatat log kunjungan halaman
    if (empty($search_keyword) && !isset($_GET['ajax'])) {
        write_log($conn, "Membuka halaman Log Riwayat Perpindahan Asset", "asset_movements", null);
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

<!-- SCRIPT LOCK POSISI SCROLL SIDEBAR UTAMA -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Mendeteksi area scroll sidebar Anda
        const sidebarBody = document.querySelector(".hide-scrollbar");
        
        if (sidebarBody) {
            // 1. Ambil dan pulihkan posisi scroll terakhir dari memori browser
            const savedScrollTop = sessionStorage.getItem("sidebarScrollPosition");
            if (savedScrollTop !== null) {
                sidebarBody.scrollTop = parseInt(savedScrollTop, 10);
            }

            // 2. Rekam posisi koordinat setiap kali menu di-scroll ke bawah/atas
            sidebarBody.addEventListener("scroll", function () {
                sessionStorage.setItem("sidebarScrollPosition", sidebarBody.scrollTop);
            });
        }
        
        // 3. Otomatis fokus menarik menu yang sedang aktif agar langsung terlihat
        const activeMenu = document.querySelector(".hide-scrollbar .active");
        if (activeMenu && !sessionStorage.getItem("sidebarScrollPosition")) {
            activeMenu.scrollIntoView({ block: "nearest" });
        }
    });
</script>

</body>
</html>
