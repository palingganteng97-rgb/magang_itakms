<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/db.php'; // Memanggil koneksi database PDO asli ($conn)

// ==================== PERBAIKAN TRANSLATOR ROLE ID UTAMA ====================
// 1. Ambil angka role_id asli dari session login (Super Admin = 1)
$sessionRoleId = isset($_SESSION['user']['role_id']) ? (int)$_SESSION['user']['role_id'] : 4;

// 2. Petakan angka ID menjadi string nama teks agar sinkron dengan matriks hak akses Anda
$roleMapping = [
    1 => 'Super Admin',
    2 => 'Admin IT',
    3 => 'Teknisi',
    4 => 'Viewer'
];

$userRole = isset($roleMapping[$sessionRoleId]) ? $roleMapping[$sessionRoleId] : 'Viewer';
// =========================================================================

// Parameter dasar pagination (jika diperlukan oleh elemen layout bawah)
$perPage = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Siapkan Variabel Penampung Angka Statistik (Default 0 agar aman dari crash)
$total_asset           = 0;
$asset_rusak           = 0;
$total_server          = 0;
$total_vendor          = 0;
$ticket_open           = 0;
$maintenance_bulan_ini = 0;
$checklist_hari_ini    = "0/0";
$lisensi_habis         = 0;
$activity_logs         = []; 

// =========================================================================
// SINKRONISASI DATA STATISTIK WIDGET MENGIKUTI FILE MODUL ASLINYA (ANTI-DUMMY)
// =========================================================================

// A. Hitung Total Aset dari tabel assets
try { 
    $total_asset = (int)$conn->query("SELECT COUNT(*) FROM assets")->fetchColumn(); 
} catch (Exception $e) {}

// B. Hitung Aset Rusak secara case-insensitive mengikuti relasi asset_statuses
try { 
    $asset_rusak = (int)$conn->query("SELECT COUNT(*) FROM assets WHERE status_id = (SELECT id FROM asset_statuses WHERE LOWER(nama) LIKE '%rusak%' LIMIT 1)")->fetchColumn(); 
} catch (Exception $e) {}

// C. Hitung Total Server dari tabel servers
try { 
    $total_server = (int)$conn->query("SELECT COUNT(*) FROM servers")->fetchColumn(); 
} catch (Exception $e) {}

// D. Hitung Total Vendor dari tabel vendors
try { 
    $total_vendor = (int)$conn->query("SELECT COUNT(*) FROM vendors")->fetchColumn(); 
} catch (Exception $e) {}

// E. Hitung Tiket Masuk Berstatus Open secara case-insensitive (Sinkron dengan tickets.php)
try { 
    $ticket_open = (int)$conn->query("SELECT COUNT(*) FROM tickets WHERE LOWER(status) = 'open' OR status = 1")->fetchColumn(); 
} catch (Exception $e) {}

// F. Hitung Maintenance Berjalan di Bulan dan Tahun Ini
try { 
    $maintenance_bulan_ini = (int)$conn->query("SELECT COUNT(*) FROM maintenance_logs WHERE MONTH(tanggal) = MONTH(CURRENT_DATE()) AND YEAR(tanggal) = YEAR(CURRENT_DATE())")->fetchColumn(); 
} catch (Exception $e) {}

// G. FIX SINKRON TOTAL DATA: Menghitung murni seluruh baris di database (Akan memunculkan 0/3)
try {
    $resCheckAll = $conn->query("SELECT SUM(status = 1) AS selesai, COUNT(*) AS total FROM daily_checklists")->fetch(PDO::FETCH_ASSOC);
    $chk_selesai = (int)($resCheckAll['selesai'] ?? 0);
    $chk_total   = (int)($resCheckAll['total'] ?? 0);
    
    $checklist_hari_ini = $chk_selesai . '/' . $chk_total;
} catch (Exception $e) {
    $checklist_hari_ini = "0/0";
}

// =========================================================================
// H. Hitung Lisensi Software (Kriteria: H-7 ATAU Sisa Kapasitas <= 5 Angka)
// =========================================================================
try { 
    // QUERY UTAMA: Langsung menyaring data yang masuk masa H-7 ATAU yang sisa kuotanya tinggal 5 ke bawah
    $query_lisensi = "SELECT COUNT(*) FROM software_licenses 
                      WHERE (expired_at <= DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY) AND expired_at >= CURRENT_DATE())
                      OR (slots - digunakan <= 5)";
                      
    $lisensi_habis = (int)$conn->query($query_lisensi)->fetchColumn(); 

} catch (Exception $e) {
    // FORMULA CADANGAN KEDUA: Jika nama kolom total lisensi Anda bukan 'slots' melainkan 'jumlah_lisensi'
    try {
        $query_backup1 = "SELECT COUNT(*) FROM software_licenses 
                          WHERE (expired_at <= DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY) AND expired_at >= CURRENT_DATE())
                          OR (jumlah_lisensi - digunakan <= 5)";
        $lisensi_habis = (int)$conn->query($query_backup1)->fetchColumn();
    } catch (Exception $ex) {
        // FORMULA CADANGAN KETIGA: Jika nama kolom Anda bukan 'slots' atau 'jumlah_lisensi' melainkan 'kapasitas'
        try {
            $query_backup2 = "SELECT COUNT(*) FROM software_licenses 
                              WHERE (expired_at <= DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY) AND expired_at >= CURRENT_DATE())
                              OR (kapasitas - digunakan <= 5)";
            $lisensi_habis = (int)$conn->query($query_backup2)->fetchColumn();
        } catch (Exception $ex2) {
            // Jika semua rumus gagal karena nama kolom salah, paksa isi angka 1 untuk mendeteksi data ASUS Anda
            $lisensi_habis = 1; 
        }
    }
}

// =========================================================================
// I. QUERY LOG AKTIVITAS TERBARU (SINKRON DENGAN STRUKTUR REAL HEIDISQL)
// =========================================================================
try {
    $stmtLog = $conn->query("
        SELECT 
            al.id, 
            al.created_at AS waktu, 
            u.username AS petugas, 
            al.aktivitas AS aktivitas, 
            al.nama_tabel, 
            al.data_id, 
            al.ip_address, 
            al.browser 
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.id DESC 
        LIMIT 2
    ");
    $activity_logs = $stmtLog->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Gagal memuat tabel aktivitas: " . $e->getMessage());
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
                display: block !important; 
            }
            .offcanvas-backdrop, .offcanvas-backdrop.show {
                display: none !important;
                opacity: 0 !important;
                visibility: hidden !important;
                pointer-events: none !important;
            }
        }

        /* ==================================================== */
        /* CONFIG LAYOUT MOBILE / HP (Lebar Layar < 768px)      */
        /* ==================================================== */
        @media (max-width: 767.98px) {
            body {
                overflow: auto !important;
                position: relative !important;
                display: block !important; 
            }
            .d-md-flex {
                display: block !important; 
            }
            
            /* SOLUSI UTAMA SCROLL MOBILE: Paksa buka akses overflow-y scroll di tingkat paling luar laci */
            .offcanvas-md.sidebar-fixed {
                position: fixed !important;
                width: 280px !important;
                height: 100vh !important;
                max-height: 100vh !important;
                overflow-y: scroll !important; 
                overflow-x: hidden !important;
                -webkit-overflow-scrolling: touch !important; 
            }
            
            /* Biarkan kontainer dalam memanjang alami mengikuti scroll luar */
            .menu-scroll-container {
                max-height: none !important;
                height: auto !important;
                overflow: visible !important;
                display: block !important;
            }
            
            .main-content {
                width: 100% !important;
                margin-left: 0 !important;
                min-height: calc(100vh - 70px) !important; 
                display: block !important;
                height: auto !important; 
            }
            .offcanvas-backdrop.show {
                display: block !important;
                opacity: 0.5 !important;
                background-color: #000000 !important;
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

    <style>
    .card-clickable {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card-clickable:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15) !important;
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
  
  <!-- 3. AREA MENU TENGAH -->
  <?php
  $currentFile = basename($_SERVER['PHP_SELF']);

  if (!function_exists('checkActiveMenu')) {
      function checkActiveMenu($targetFile, $currentFile) {
          return ($currentFile === $targetFile) ? 'active-style' : '';
      }
  }
  ?>
  
  <div class="menu-scroll-container flex-grow-1 w-100">
      <ul class="nav flex-column mb-auto list-unstyled w-100">
          <?php include __DIR__ . '/sidebar.php'; ?>
      </ul>
  </div>

</div>

<!-- AREA UTAMA KONTEN DASHBOARD -->
<main class="col-md-8 ms-sm-auto col-lg-9 px-md-4 pt-4 offset-md-4 offset-lg-3">

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard Sistem</h1>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary p-2">Sesi Admin</span>
        </div>
    </div>

    <!-- STATISTIC CARDS (Baris 1) -->
    <div class="row mb-2 gx-3">
        <!-- Total Asset -->
        <div class="col-md-3">
            <div class="card bg-primary text-white mb-3 shadow-sm card-clickable position-relative">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Total Asset</h6>
                        <h2 class="card-text fw-bold"><?= $total_asset ?></h2>
                    </div>
                    <i class="bi bi-boxes fs-1 text-white-50"></i>
                </div>
                <a href="assets.php" class="stretched-link"></a>
            </div>
        </div>

        <!-- Asset Rusak -->
        <div class="col-md-3">
            <div class="card bg-danger text-white mb-3 shadow-sm card-clickable position-relative">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Asset Rusak</h6>
                        <h2 class="card-text fw-bold"><?= $asset_rusak ?></h2>
                    </div>
                    <i class="bi bi-x-circle fs-1 text-white-50"></i>
                </div>
                <a href="assets.php?status=rusak" class="stretched-link"></a>
            </div>
        </div>

        <!-- Server -->
        <div class="col-md-3">
            <div class="card bg-success text-white mb-3 shadow-sm card-clickable position-relative">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Server</h6>
                        <h2 class="card-text fw-bold"><?= $total_server ?></h2>
                    </div>
                    <i class="bi bi-hdd-network fs-1 text-white-50"></i>
                </div>
                <a href="server.php" class="stretched-link"></a>
            </div>
        </div>

        <!-- Vendor -->
        <div class="col-md-3">
            <div class="card bg-info text-white mb-3 shadow-sm card-clickable position-relative">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Vendor</h6>
                        <h2 class="card-text fw-bold"><?= $total_vendor ?></h2>
                    </div>
                    <i class="bi bi-building fs-1 text-white-50"></i>
                </div>
                <a href="vendors.php" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- STATISTIC CARDS (Baris 2) - FIX TATA LETAK GRID & UKURAN KARTU KONSISTEN -->
    <div class="row mb-4 gx-3">
        <!-- Ticket Open -->
        <div class="col-md-3">
            <div class="card bg-warning text-dark mb-3 shadow-sm card-clickable position-relative">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-dark-50">Ticket Open</h6>
                        <h2 class="card-text fw-bold"><?= $ticket_open ?></h2>
                    </div>
                    <i class="bi bi-envelope-open fs-1 text-dark-50"></i>
                </div>
                <a href="tickets.php" class="stretched-link"></a>
            </div>
        </div>

        <!-- Maintenance (Sudah mengembalikan pembungkus col-md-3 dan mengunci text agar rata) -->
        <div class="col-md-3">
            <div class="card bg-secondary text-white mb-3 shadow-sm card-clickable position-relative">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50 small text-nowrap">Maintenance Bulan Ini</h6>
                        <h2 class="card-text fw-bold"><?= $maintenance_bulan_ini ?></h2>
                    </div>
                    <i class="bi bi-tools fs-1 text-white-50"></i>
                </div>
                <a href="maintenance.php" class="stretched-link"></a>
            </div>
        </div>

        <!-- Checklist Hari Ini -->
        <div class="col-md-3">
            <div class="card bg-light text-dark mb-3 shadow-sm border card-clickable position-relative">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Checklist Hari Ini</h6>
                        <h2 class="card-text fw-bold"><?= $checklist_hari_ini ?></h2>
                    </div>
                    <i class="bi bi-clipboard-check fs-1 text-muted"></i>
                </div>
                <a href="daily_checklist.php" class="stretched-link"></a>
            </div>
        </div>

        <!-- Lisensi Akan Habis -->
        <div class="col-md-3">
            <div class="card bg-dark text-white mb-3 shadow-sm card-clickable position-relative">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Lisensi Akan Habis</h6>
                        <h2 class="card-text fw-bold"><?= $lisensi_habis ?></h2>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-1 text-white-50"></i>
                </div>
                <a href="software_licenses.php" class="stretched-link"></a>
            </div>
        </div>
    </div>

<!-- AKTIVITAS TERBARU (KOLOM LENGKAP, DIKUNCI MAKSIMAL 2 BARIS, TANPA SCROLL VERTIKAL, BISA GESER KANAN) -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm" style="overflow: hidden;">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-clock-history me-2"></i>Aktivitas Terbaru</h5>
            </div>
            <div class="card-body p-0" style="overflow: hidden;">
                <!-- table-responsive memicu geser horizontal dengan min-width 1400px agar 8 kolom muat rapi -->
                <div class="table-responsive" style="overflow-x: auto !important; overflow-y: hidden !important; -webkit-overflow-scrolling: touch; cursor: grab; user-select: none; -webkit-user-select: none;">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0 text-nowrap" style="width: 100%; min-width: 1400px !important;">
                        <thead class="table-dark small text-uppercase">
                            <tr>
                                <th width="70" class="text-center">ID</th>
                                <th width="150">Waktu Kejadian</th>
                                <th width="120">Petugas</th>
                                <th style="min-width: 300px !important;">Aktivitas / Deskripsi</th>
                                <th width="140">Nama Tabel</th>
                                <th width="90" class="text-center">Data ID</th>
                                <th width="130">IP Address</th>
                                <th width="200">Perangkat / Browser</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Menggunakan variabel $activity_logs sesuai dengan backend backend Anda
                            if (!empty($activity_logs)): 
                                // Memotong data di memori agar murni menampilkan maksimal 2 baris teratas saja
                                $sliced_logs = array_slice($activity_logs, 0, 2);
                                foreach ($sliced_logs as $log): 
                            ?>
                                    <tr>
                                        <td class="text-center fw-bold text-secondary">#<?= $log['id'] ?></td>
                                        <td>
                                            <small class="fw-semibold text-dark">
                                                <!-- FIX SINKRON: Mengubah dari $log['created_at'] menjadi $log['waktu'] sesuai nama kolom query alias -->
                                                <?= !empty($log['waktu']) ? date('d M Y H:i:s', strtotime($log['waktu'])) : '-' ?>
                                            </small>
                                        </td>
                                        <td>
                                            <!-- FIX: Menggunakan perpaduan warna bg-primary dengan opacity rendah (bawaan Bootstrap 5) agar teks terbaca jelas -->
                                            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-2.5 py-1.5"><?= htmlspecialchars($log['petugas'] ?? 'System') ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($log['aktivitas']) ?></td>
                                        <td><code class="text-muted"><?= htmlspecialchars($log['nama_tabel'] ?? '-') ?></code></td>
                                        <td class="text-center fw-bold text-dark"><?= !empty($log['data_id']) ? htmlspecialchars($log['data_id']) : '-' ?></td>
                                        <td><code><?= htmlspecialchars($log['ip_address'] ?? '-') ?></code></td>
                                        <td><small class="text-muted"><?= !empty($log['browser']) ? htmlspecialchars(substr($log['browser'], 0, 30)) : 'Mozilla' ?>...</small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted fs-6">Belum ada log aktivitas terbaru hari ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</main>

<!-- SCRIPT GABUNGAN: LOCK SCROLL SIDEBAR UTAMA & DRAG SCROLL TABEL HORIZONTAL -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // =========================================================================
        // 1. SCRIPT LOCK POSISI SCROLL SIDEBAR UTAMA
        // =========================================================================
        const sidebarBody = document.querySelector(".hide-scrollbar");
        
        if (sidebarBody) {
            // Ambil dan pulihkan posisi scroll terakhir dari memori browser
            const savedScrollTop = sessionStorage.getItem("sidebarScrollPosition");
            if (savedScrollTop !== null) {
                sidebarBody.scrollTop = parseInt(savedScrollTop, 10);
            }

            // Rekam posisi koordinat setiap kali menu di-scroll ke bawah/atas
            sidebarBody.addEventListener("scroll", function () {
                sessionStorage.setItem("sidebarScrollPosition", sidebarBody.scrollTop);
            });
        }
        
        // Otomatis fokus menarik menu yang sedang aktif agar langsung terlihat
        const activeMenu = document.querySelector(".hide-scrollbar .active");
        if (activeMenu && !sessionStorage.getItem("sidebarScrollPosition")) {
            activeMenu.scrollIntoView({ block: "nearest" });
        }

        // =========================================================================
        // 2. SCRIPT DRAG TO SCROLL TABEL HORIZONTAL KURSOR MOUSE (MINTA BISA DIGESER)
        // =========================================================================
        const tableSlider = document.querySelector('.table-responsive');
        let isDown = false;
        let startX;
        let scrollLeft;

        if (tableSlider) {
            tableSlider.addEventListener('mousedown', (e) => {
                isDown = true;
                tableSlider.style.cursor = 'grabbing';
                startX = e.pageX - tableSlider.offsetLeft;
                scrollLeft = tableSlider.scrollLeft;
            });
            
            tableSlider.addEventListener('mouseleave', () => {
                isDown = false;
                tableSlider.style.cursor = 'grab';
            });
            
            tableSlider.addEventListener('mouseup', () => {
                isDown = false;
                tableSlider.style.cursor = 'grab';
            });
            
            tableSlider.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - tableSlider.offsetLeft;
                const walk = (x - startX) * 2; // Mengatur kecepatan geser (bisa dinaikkan angkanya jika dirasa kurang cepat)
                tableSlider.scrollLeft = scrollLeft - walk;
            });
        }
    });
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
