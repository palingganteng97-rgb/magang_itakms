<?php
// ====================================================================
// LOGIKA BACKEND UTAMA - ACTIVITY LOGS VIEW & PAGINATION
// ====================================================================

require_once __DIR__ . '/auth.php';
require_login();

// SISPANKAN PROTEKSI RBAC DI SINI
require_once __DIR__ . '/helper_rbac.php';
protect_page_by_table('activity_logs', 'R'); // Mengunci halaman hanya untuk Super Admin & Admin IT

require_once __DIR__ . '/db.php'; 

// =========================================================================
// 1. KONFIGURASI DATABASE & PAGINASI
// =========================================================================
$currentFile = 'activity_logs.php';
$message = '';
$message_type = '';

// Paginasi: Membatasi tampilan 50 baris per halaman
$perPage = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

try {
    // Memastikan koneksi PDO terbentuk dengan memanfaatkan variabel dari db.php
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Memindahkan isi flash pesan jika ada sisa redirect dari halaman lain
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $message_type = 'success';
        unset($_SESSION['flash_message']);
    }
    if (isset($_SESSION['flash_error'])) {
        $message = $_SESSION['flash_error'];
        $message_type = 'danger';
        unset($_SESSION['flash_error']);
    }

    // TRIGGER LOG OTOMATIS GLOBAL: Berhasil dipanggil tanpa error redeclare
    if (!isset($_GET['search']) && !isset($_GET['page']) && !isset($_GET['ajax'])) {
        write_log($conn, "Membuka halaman Log Aktivitas Sistem", "activity_logs", null);
    }

    // =========================================================================
    // 2. AMBIL DATA LOGS & AMBIL DATA PAGINASI (READ DENGAN LEFT JOIN USERS)
    // =========================================================================
    $search = trim($_GET['search'] ?? '');
    
    // Hitung total baris untuk paginasi
    if (!empty($search)) {
        $countStmt = $conn->prepare("SELECT COUNT(*) FROM activity_logs al 
                                     LEFT JOIN users u ON al.user_id = u.id 
                                     WHERE al.aktivitas LIKE :search OR al.nama_tabel LIKE :search OR u.username LIKE :search");
        $countStmt->execute([':search' => "%$search%"]);
    } else {
        $countStmt = $conn->query("SELECT COUNT(*) FROM activity_logs");
    }
    $totalRows = $countStmt->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);

    // Ambil data log aktivitas dengan relasi username petugas
    if (!empty($search)) {
        $sql = "SELECT al.*, u.username FROM activity_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                WHERE al.aktivitas LIKE :search OR al.nama_tabel LIKE :search OR u.username LIKE :search 
                ORDER BY al.id DESC LIMIT :limit OFFSET :offset";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    } else {
        $sql = "SELECT al.*, u.username FROM activity_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                ORDER BY al.id DESC LIMIT :limit OFFSET :offset";
        $stmt = $conn->prepare($sql);
    }
    
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $activity_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================================
    // INTERSEPTOR AUTOMATIC LOGS REAL-TIME (AJAX HANDLER) - SESUAI HEIDISQL
    // =========================================================================
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        if (empty($activity_logs)) {
            echo "<tr>
                    <td colspan='8' class='text-center py-5 text-muted fs-6'>
                        <i class='bi bi-clipboard-x display-6 d-block mb-2 text-secondary'></i>
                        Belum ada log aktivitas.
                    </td>
                  </tr>";
        } else {
            foreach ($activity_logs as $log) {
                $id           = htmlspecialchars($log['id']);
                $waktu        = !empty($log['created_at']) ? date('d M Y H:i:s', strtotime($log['created_at'])) : '-';
                $petugas      = htmlspecialchars($log['username'] ?? 'admin');
                $aktivitas    = htmlspecialchars($log['aktivitas']);
                $tabel        = htmlspecialchars($log['nama_tabel']);
                $data_id      = !empty($log['data_id']) ? htmlspecialchars($log['data_id']) : '-';
                $ip_address   = htmlspecialchars($log['ip_address']);
                $browser_short= !empty($log['browser']) ? htmlspecialchars(substr($log['browser'], 0, 15)) : 'Mozilla';

                echo "<tr>
                        <td class='text-center fw-bold text-secondary'>#{$id}</td>
                        <td><small class='fw-semibold text-dark'>{$waktu}</small></td>
                        <td><span class='badge bg-light-primary text-primary'><i class='fa fa-user'></i> {$petugas}</span></td>
                        <td>{$aktivitas}</td>
                        <td><code class='text-muted'>{$tabel}</code></td>
                        <td class='text-center fw-bold text-dark'>{$data_id}</td>
                        <td><code>{$ip_address}</code></td>
                        <td><small class='text-muted'>{$browser_short}...</small></td>
                      </tr>";
            }
        }
        exit; // Hentikan script agar sisa layout HTML di bawah tidak ikut terkirim ke AJAX
    }

} catch (PDOException $e) {
    die("Koneksi atau Query Log Aktivitas Gagal: " . $e->getMessage());
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

<!-- AREA UTAMA KONTEN -->
<main class="col-md-8 ms-sm-auto col-lg-9 px-md-4 pt-4 offset-md-4 offset-lg-3">

    <!-- BAGIAN JUDUL UTAMA -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-clock-history me-2 text-primary"></i>Log Aktivitas Sistem</h1>
    </div>

    <!-- Elemen pembungkus .card agar tabel memiliki latar putih yang rapi -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <!-- Alert Notifikasi Feedback -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $message_type; ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="bi <?= $message_type == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i> 
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Bilah Pencarian Data (Berdasarkan Aktivitas/Tabel/Petugas) -->
            <form method="GET" class="row g-2 mb-4">
                <div class="col-sm-5 col-md-4">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="Cari aktivitas, tabel, petugas..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-secondary">Cari</button>
                    </div>
                </div>
                <?php if (!empty($search)): ?>
                    <div class="col-sm-2">
                        <a href="?" class="btn btn-sm btn-outline-secondary text-decoration-none">Reset Filter</a>
                    </div>
                <?php endif; ?>
            </form>

<!-- Tabel Utama Penampil Data Log Aktivitas -->
<!-- FIX: Menambahkan max-height 380px untuk membatasi tampilan maksimal 7 baris data dan mengaktifkan scroll vertikal -->
<div class="table-responsive" style="max-height: 380px !important; overflow-y: auto !important; overflow-x: auto !important; cursor: grab; user-select: none; -webkit-user-select: none; display: block; width: 100%;">
    <table class="table table-bordered table-striped table-hover align-middle mb-0 text-nowrap" style="width: 100%; min-width: 1400px !important;">
        <thead class="table-dark small text-uppercase" style="position: sticky; top: 0; z-index: 1020;">
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
        <tbody id="logTableBody">
            <?php if (empty($activity_logs)): ?>
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted fs-6">
                        <i class="bi bi-clipboard-x display-6 d-block mb-2 text-secondary"></i>
                        Belum ada log aktivitas.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($activity_logs as $log): ?>
                    <tr>
                        <td class="text-center fw-bold text-secondary">#<?= $log['id'] ?></td>
                        <td>
                            <small class="fw-semibold text-dark">
                                <?= !empty($log['created_at']) ? date('d M Y H:i:s', strtotime($log['created_at'])) : '-' ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-light-primary text-primary"><?= htmlspecialchars($log['username'] ?? 'admin') ?></span>
                        </td>
                        <td><?= htmlspecialchars($log['aktivitas']) ?></td>
                        <td><code class="text-muted"><?= htmlspecialchars($log['nama_tabel']) ?></code></td>
                        <td class="text-center fw-bold text-dark"><?= !empty($log['data_id']) ? htmlspecialchars($log['data_id']) : '-' ?></td>
                        <td><code><?= htmlspecialchars($log['ip_address']) ?></code></td>
                        <td><small class="text-muted"><?= !empty($log['browser']) ? htmlspecialchars(substr($log['browser'], 0, 30)) : 'Mozilla' ?>...</small></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Bagian Paginasi Halaman Konten -->
<?php if ($totalPages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination pagination-sm justify-content-center mb-0">
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

</div> <!-- Penutup .card-body -->
</div> <!-- Penutup .card -->

</main> <!-- Penutup <main> -->

<!-- SCRIPTS OTOMATIS REAL-TIME & DRAG SCROLL KURSOR PERMANEN -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.querySelector('input[name="search"]') || document.querySelector('.form-control');
    const slider = document.querySelector('.table-responsive'); // Menarget wadah luar permanen
    
    // =========================================================================
    // 1. FUNGSI LOG REAL-TIME AMAN (DENGAN COAK CEK BARIS TR)
    // =========================================================================
    function fetchRealtimeLogs() {
        if (searchInput && document.activeElement === searchInput && searchInput.value.trim() !== "") {
            return; 
        }

        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('ajax', '1');

        fetch('activity_logs.php?' + urlParams.toString())
            .then(response => {
                if (response.redirected || response.url.includes('login.php')) {
                    window.location.href = 'login.php';
                    return;
                }
                return response.text();
            })
            .then(htmlData => {
                if (htmlData && htmlData.includes('<tr>')) {
                    const tableBody = document.getElementById('logTableBody');
                    if (tableBody) {
                        tableBody.innerHTML = htmlData; // Isi tabel diganti, wadah .table-responsive tetap utuh
                    }
                } else if (htmlData && htmlData.includes('login')) {
                    window.location.href = 'login.php';
                }
            })
            .catch(error => console.warn('Gagal memuat log waktu nyata:', error));
    }

    // Jalankan pengecekan log baru setiap 2 detik
    setInterval(fetchRealtimeLogs, 2000);

    // =========================================================================
    // 2. FUNGSI DRAG TO SCROLL PERMANEN (TIDAK AKAN LEPAS SAAT LIVE UPDATE)
    // =========================================================================
    let isDown = false;
    let startX;
    let scrollLeft;

    if (slider) {
        slider.addEventListener('mousedown', (e) => {
            isDown = true;
            slider.style.cursor = 'grabbing';
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });
        
        slider.addEventListener('mouseleave', () => {
            isDown = false;
            slider.style.cursor = 'grab';
        });
        
        slider.addEventListener('mouseup', () => {
            isDown = false;
            slider.style.cursor = 'grab';
        });
        
        slider.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - slider.offsetLeft;
            const walk = (x - startX) * 2; // Mengatur kecepatan geser tabel (Angka 2 bisa dinaikkan jika kurang cepat)
            slider.scrollLeft = scrollLeft - walk;
        });
    }
});
</script>

<!-- Script Pengendali Logika Alur Pengisian Form Modal -->
<script>

// Fungsi menyuntikkan data kategori ke dalam elemen modal hapus sebelum ditampilkan
function prepareDelete(id, nama) {
    document.getElementById('hapusCategoryId').value = id;
    document.getElementById('hapusCategoryName').innerText = nama;
}

function prepareCreate() {
    document.getElementById('modalTitle').innerText = 'Tambah Kategori';
    document.getElementById('formAction').value = 'create';
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryName').value = '';
}

function prepareUpdate(id, nama) {
    document.getElementById('modalTitle').innerText = 'Edit Kategori';
    document.getElementById('formAction').value = 'update';
    document.getElementById('categoryId').value = id;
    document.getElementById('categoryName').value = nama;
}
</script>
<script>
// INTERAKSI DOM DAN SINKRONISASI POSISI SCROLL SIDEBAR UTAMA (KUNCI ABSOLUT)
document.addEventListener("DOMContentLoaded", function() {
    const menuContainer = document.querySelector('.menu-scroll-container');
    const activeMenu = document.querySelector('.menu-scroll-container .active-style');
    
    // FIX MUTLAK: Selalu menetap mengunci fokus menu aktif saat halaman selesai dimuat (klik / reload)
    if (menuContainer && activeMenu) {
        const activeOffsetTop = activeMenu.offsetTop;
        menuContainer.scrollTop = activeOffsetTop - 20;
    }
});
</script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
