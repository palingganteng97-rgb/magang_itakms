<?php
require_once __DIR__ . '/auth.php';
require_login();

// =========================================================================
// 1. KONFIGURASI DATABASE
// =========================================================================
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

// Paginasi
$perPage = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // =========================================================================
    // 2. PROSES AKSI POST (CREATE, UPDATE, DELETE) -> DENGAN REDIRECT (ANTI-F5)
    // =========================================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Menangkap parameter halaman dan pencarian aktif agar setelah redirect tidak kembali ke halaman 1
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $currentSearch = isset($_GET['search']) ? trim($_GET['search']) : '';
        $redirectUrl = $_SERVER['PHP_SELF'] . "?page=" . $currentPage;
        if (!empty($currentSearch)) {
            $redirectUrl .= "&search=" . urlencode($currentSearch);
        }

        // --- TAMBAH DATA (CREATE) ---
        if (isset($_POST['action']) && $_POST['action'] === 'create') {
            $nama = trim($_POST['nama'] ?? '');
            if (!empty($nama)) {
                $stmt = $conn->prepare("INSERT INTO knowledge_categories (nama) VALUES (:nama)");
                $sukses_create = $stmt->execute([':nama' => $nama]);
                
                // AMBIL ID BARU DAN TULIS LOG AKTIVITAS (CREATE)
                if ($sukses_create) {
                    $new_cat_id = $conn->lastInsertId();
                    write_log($conn, "Menambahkan kategori knowledge baru: " . $nama, "knowledge_categories", $new_cat_id);
                }
                
                $_SESSION['flash_message'] = "Kategori berhasil ditambahkan.";
            } else {
                $_SESSION['flash_error'] = "Nama kategori tidak boleh kosong.";
            }
            header("Location: " . $redirectUrl);
            exit;
        }

        // --- UBAH DATA (UPDATE) ---
        if (isset($_POST['action']) && $_POST['action'] === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $nama = trim($_POST['nama'] ?? '');
            if ($id > 0 && !empty($nama)) {
                $stmt = $conn->prepare("UPDATE knowledge_categories SET nama = :nama WHERE id = :id");
                $sukses_update = $stmt->execute([':nama' => $nama, ':id' => $id]);
                
                // TULIS LOG AKTIVITAS (UPDATE)
                if ($sukses_update) {
                    write_log($conn, "Mengubah data kategori knowledge menjadi: " . $nama, "knowledge_categories", $id);
                }
                
                $_SESSION['flash_message'] = "Kategori berhasil diperbarui.";
            } else {
                $_SESSION['flash_error'] = "Data tidak valid atau nama kosong.";
            }
            header("Location: " . $redirectUrl);
            exit;
        }

        // --- HAPUS DATA (DELETE) ---
        if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                // 1. Ambil nama kategori terlebih dahulu sebelum dihapus permanen
                $get_name = $conn->prepare("SELECT nama FROM knowledge_categories WHERE id = :id");
                $get_name->execute([':id' => $id]);
                $nama_kategori = $get_name->fetchColumn() ?: 'Unknown';

                // 2. Jalankan query hapus data
                $stmt = $conn->prepare("DELETE FROM knowledge_categories WHERE id = :id");
                $sukses_delete = $stmt->execute([':id' => $id]);
                
                // TULIS LOG AKTIVITAS (DELETE)
                if ($sukses_delete) {
                    write_log($conn, "Menghapus kategori knowledge: " . $nama_kategori, "knowledge_categories", $id);
                }
                
                $_SESSION['flash_message'] = "Kategori berhasil dihapus.";
            } else {
                $_SESSION['flash_error'] = "Gagal menghapus data, ID tidak valid.";
            }
            header("Location: " . $redirectUrl);
            exit;
        }
    }

    // Memindahkan isi pesan dari Session ke variabel lokal agar bisa dibaca oleh HTML di bawahnya
    $message = $_SESSION['flash_message'] ?? '';
    $error = $_SESSION['flash_error'] ?? '';
    
    // Langsung hapus (unset) dari session supaya pesan otomatis hilang ketika di-refresh (F5) berikutnya
    unset($_SESSION['flash_message'], $_SESSION['flash_error']);

    // =========================================================================
    // 3. AMBIL DATA & PAGINASI (READ)
    // =========================================================================
    $search = trim($_GET['search'] ?? '');
    
    // Hitung total baris untuk paginasi
    if (!empty($search)) {
        $countStmt = $conn->prepare("SELECT COUNT(*) FROM knowledge_categories WHERE nama LIKE :search");
        $countStmt->execute([':search' => "%$search%"]);
    } else {
        $countStmt = $conn->query("SELECT COUNT(*) FROM knowledge_categories");
    }
    $totalRows = $countStmt->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);

    // Ambil data berdasarkan halaman aktif
    if (!empty($search)) {
        $stmt = $conn->prepare("SELECT id, nama FROM knowledge_categories WHERE nama LIKE :search ORDER BY id DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    } else {
        $stmt = $conn->prepare("SELECT id, nama FROM knowledge_categories ORDER BY id DESC LIMIT :limit OFFSET :offset");
    }
    
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Koneksi atau Query Gagal: " . $e->getMessage());
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

    <!-- BAGIAN JUDUL UTAMA & TOMBOL TAMBAH DATA (BERDAMPINGAN) -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Knowledge Categories</h1>
        <div class="d-flex align-items-center gap-2">
            
            <!-- Fleksibel Otomatis: Hanya tampil jika Role memiliki izin Create ('C') di file ini -->
            <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'C', $userRole)): ?>
            <!-- Tombol Tambah Data Berhasil Dikembalikan di Sini -->
            <button class="btn btn-primary btn-sm fw-semibold shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#modalKategori" onclick="prepareCreate()">
                + Tambah Kategori Baru
            </button>
            <?php endif; ?>
            
        </div>
    </div>

<!-- Elemen pembungkus .card ditambahkan kembali di sini agar tabel memiliki latar putih yang rapi -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <!-- Alert Notifikasi Feedback CRUD -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Bilah Pencarian Data -->
            <form method="GET" class="row g-2 mb-4">
                <div class="col-sm-5 col-md-4">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama kategori..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-secondary">Cari</button>
                    </div>
                </div>
                <?php if (!empty($search)): ?>
                    <div class="col-sm-2">
                        <a href="?" class="btn btn-sm btn-outline-secondary text-decoration-none">Reset Filter</a>
                    </div>
                <?php endif; ?>
            </form>

            <!-- Tabel Utama Penampil Data Kategori -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th width="80" class="text-center">ID</th>
                            <th>Nama Kategori</th>
                            
                            <!-- Fleksibel Otomatis: Hanya tampilkan kolom header Aksi jika user bukan Viewer -->
                            <?php if ($userRole !== 'Viewer'): ?>
                            <th width="180" class="text-center">Aksi / Operasi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="<?= ($userRole === 'Viewer') ? '2' : '3'; ?>" class="text-center py-4 text-muted fs-6">
                                    Tidak ada data kategori ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td class="text-center fw-semibold text-secondary"><?= $cat['id'] ?></td>
                                    <td><?= htmlspecialchars($cat['nama']) ?></td>
                                    
                                    <!-- Fleksibel Otomatis: Sembunyikan total blok kolom aksi bagi akun Viewer -->
                                    <?php if ($userRole !== 'Viewer'): ?>
                                    <td class="text-center">
                                        
                                        <!-- Fleksibel Otomatis: Cek Akses Update ('U') untuk tombol Edit -->
                                        <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'U', $userRole)): ?>
                                        <button class="btn btn-warning btn-sm me-1 fw-medium" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalKategori" 
                                                onclick="prepareUpdate(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['nama'], ENT_QUOTES) ?>')">
                                            Edit
                                        </button>
                                        <?php endif; ?>

                                        <!-- Fleksibel Otomatis: Cek Akses Delete ('D') untuk tombol Hapus -->
                                        <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'D', $userRole)): ?>
                                        <button class="btn btn-outline-danger btn-sm fw-medium" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalHapusKategori" 
                                                onclick="prepareDelete(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['nama'], ENT_QUOTES) ?>')">
                                            Hapus
                                        </button>
                                        <?php endif; ?>
                                        
                                    </td>
                                    <?php endif; ?>
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

<!-- Modal Dialog Bersama untuk Aksi Tambah & Edit Data -->
<div class="modal fade" id="modalKategori" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark" id="modalTitle">Tambah Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Variabel Hidden Penampung Status Kondisi -->
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="categoryId" value="">
                
                <!-- Input Atribut Field Objek -->
                <div class="mb-1">
                    <label for="categoryName" class="form-label fw-semibold text-secondary small">NAMA KATEGORI</label>
                    <input type="text" name="nama" id="categoryName" class="form-control" required placeholder="Contoh: Jaringan, Perangkat Keras">
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm px-3">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Data -->
<div class="modal fade" id="modalHapusKategori" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form method="POST" class="modal-content border-0 shadow-lg">
            <!-- Hidden Input Parameter Aksi CRUD -->
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="hapusCategoryId">

            <div class="modal-body text-center p-4">
                <!-- Icon Peringatan Animatif / Estetik -->
                <div class="text-danger mb-3">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 3.5rem;"></i>
                </div>
                
                <h5 class="fw-bold text-dark mb-2">Hapus Kategori?</h5>
                <p class="text-muted small mb-4">
                    Kategori <strong id="hapusCategoryName" class="text-dark"></strong> akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.</p>
                
                <!-- Tombol Aksi Bersandingan -->
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light w-50 fw-semibold text-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger w-50 fw-semibold">Ya, Hapus</button>
                </div>
            </div>
        </form>
    </div>
</div>

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
