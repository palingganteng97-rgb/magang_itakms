<?php
// ====================================================================
// LOGIKA BACKEND UTAMA - INSTANT REDIRECT (TANPA ALERT POP-UP)
// ====================================================================

require_once __DIR__ . '/auth.php';
require_login(); 
require_once __DIR__ . '/db.php'; 

$currentPage = 'sop_categories.php';

$message = '';
$message_type = '';

// TRIGGER LOG OTOMATIS GLOBAL: Mencatat log kunjungan halaman
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['delete'])) {
    write_log($conn, "Membuka halaman Kategori SOP", "sop_categories", null);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // === PROSES TAMBAH KATEGORI ===
    if (isset($_POST['action']) && $_POST['action'] == 'create') {
        $nama = $_POST['nama'];

        try {
            $sql = "INSERT INTO sop_categories (nama) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $sukses_add = $stmt->execute([$nama]);
            
            // AMBIL ID BARU DAN TULIS LOG AKTIVITAS (CREATE)
            if ($sukses_add) {
                $new_cat_id = $conn->lastInsertId();
                write_log($conn, "Menambahkan kategori SOP baru: " . $nama, "sop_categories", $new_cat_id);
            }
            
            echo "<script>window.location.href = 'sop_categories.php';</script>";
            exit();
        } catch (Exception $e) {
            $message = "Gagal menambah kategori: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // === PROSES UBAH KATEGORI ===
    if (isset($_POST['action']) && $_POST['action'] == 'update') {
        $id = $_POST['id'];
        $nama = $_POST['nama'];

        try {
            $sql = "UPDATE sop_categories SET nama = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $sukses_edit = $stmt->execute([$nama, $id]);
            
            // TULIS LOG AKTIVITAS (UPDATE)
            if ($sukses_edit) {
                write_log($conn, "Mengubah nama kategori SOP menjadi: " . $nama, "sop_categories", $id);
            }
            
            echo "<script>window.location.href = 'sop_categories.php';</script>";
            exit();
        } catch (Exception $e) {
            $message = "Gagal memperbarui kategori: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// === PROSES HAPUS KATEGORI ===
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        // 1. Ambil nama kategori terlebih dahulu untuk log sebelum datanya terhapus
        $get_info = $conn->prepare("SELECT nama FROM sop_categories WHERE id = ?");
        $get_info->execute([$id]);
        $nama_kategori = $get_info->fetchColumn() ?: 'Unknown';

        // 2. Jalankan query hapus data
        $sql = "DELETE FROM sop_categories WHERE id = ?";
        $sukses_delete = $conn->prepare($sql)->execute([$id]);
        
        // TULIS LOG AKTIVITAS (DELETE)
        if ($sukses_delete) {
            write_log($conn, "Menghapus kategori SOP: " . $nama_kategori, "sop_categories", $id);
        }
        
        echo "<script>window.location.href = 'sop_categories.php';</script>";
        exit();
    } catch (Exception $e) {
        $message = "Gagal menghapus kategori: " . $e->getMessage();
        $message_type = "danger";
    }
}

$categories = $conn->query("SELECT * FROM sop_categories ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
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

<!-- AREA UTAMA KONTEN (Gunakan pembungkus ini agar susunan halaman tidak bergeser tertimpa sidebar) -->
<main class="col-md-8 ms-sm-auto col-lg-9 px-md-4 pt-4 offset-md-4 offset-lg-3">

    <!-- Header Konten Utama Kategori -->
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
        <h5 class="mb-0 text-dark fw-bold">
            <i class="bi bi-tags-fill text-primary me-2"></i> Kategori SOP
        </h5>
        
        <!-- Fleksibel Otomatis: Hanya tampil jika Role memiliki izin Create ('C') di file ini -->
        <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'C', $userRole)): ?>
        <button class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddCategory">
            <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
        </button>
        <?php endif; ?>
        
    </div>

<!-- Body Utama / Tempat Tabel Data Kategori -->
    <div class="card-body p-4">
        
        <!-- Notifikasi Status CRUD -->
        <?php if(!empty($message)): ?>
            <div class="alert alert-<?= $message_type; ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi <?= $message_type == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
                <?= $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Tabel Responsif Kategori (table-bordered) -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle m-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th width="10%" class="text-center">ID</th>
                        <th width="<?= (!in_array($userRole, ['Teknisi', 'Viewer'])) ? '75%' : '90%'; ?>">Nama Kategori</th>
                        
                        <!-- Fleksibel Otomatis: Hanya tampilkan kolom header Aksi jika user bukan Teknisi & Viewer -->
                        <?php if (!in_array($userRole, ['Teknisi', 'Viewer'])): ?>
                        <th width="15%" class="text-center">Aksi</th>
                        <?php endif; ?>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($categories) == 0): ?>
                        <tr>
                            <td colspan="<?= (!in_array($userRole, ['Teknisi', 'Viewer'])) ? '3' : '2'; ?>" class="text-center text-muted py-5">
                                <i class="bi bi-folder-x display-6 d-block mb-2 text-secondary"></i>
                                Belum ada data kategori SOP yang tersimpan.
                            </td>
                        </tr>
                    <?php endif; ?>
                    
                    <?php foreach($categories as $row): ?>
                    <tr>
                        <td class="text-center"><span class="text-muted fw-bold">#<?= $row['id']; ?></span></td>
                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama']); ?></td>
                        
                        <!-- Fleksibel Otomatis: Blok kolom aksi disembunyikan total dari Teknisi & Viewer (X) -->
                        <?php if (!in_array($userRole, ['Teknisi', 'Viewer'])): ?>
                        <td class="text-center">
                            <div class="btn-group shadow-sm border rounded bg-white">
                                
                                <!-- Fleksibel Otomatis: Cek Akses Update ('U') untuk tombol Edit -->
                                <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'U', $userRole)): ?>
                                <button class="btn btn-sm text-warning border-0" data-bs-toggle="modal" data-bs-target="#modalEditCategory<?= $row['id']; ?>" title="Ubah Kategori">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <?php endif; ?>
                                
                                <!-- Fleksibel Otomatis: Cek Akses Delete ('D') untuk tombol Hapus -->
                                <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'D', $userRole)): ?>
                                <button class="btn btn-sm text-danger border-0 border-start" data-bs-toggle="modal" data-bs-target="#modalDeleteCategory<?= $row['id']; ?>" title="Hapus Kategori">
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                                <?php endif; ?>
                                
                            </div>
                        </td>
                        <?php endif; ?>
                        
                    </tr>

                    <!-- ========================================== -->
                    <!-- MODAL EDIT KATEGORI (HORIZONTAL RATA KIRI)  -->
                    <!-- ========================================== -->
                    <div class="modal fade" id="modalEditCategory<?= $row['id']; ?>" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg rounded-3">
                          <form action="sop_categories.php" method="POST">
                              <input type="hidden" name="action" value="update">
                              <input type="hidden" name="id" value="<?= $row['id']; ?>">
                              
                              <div class="modal-header border-bottom-0 pb-0">
                                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-warning me-2"></i> Ubah Kategori</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              
                              <div class="modal-body pt-3">
                                <div class="row align-items-center">
                                    <label for="nama_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-start">Nama Kategori</label>
                                    <div class="col-8">
                                        <input type="text" id="nama_<?= $row['id']; ?>" name="nama" class="form-control form-control-sm text-dark" value="<?= htmlspecialchars($row['nama']); ?>" required>
                                    </div>
                                </div>
                              </div>
                              
                              <div class="modal-footer border-top-0 pt-0">
                                <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Tutup</button>
                                <button type="submit" class="btn btn-sm btn-warning fw-bold text-white px-4 shadow-sm">Update</button>
                              </div>
                          </form>
                        </div>
                      </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- MODAL HAPUS KATEGORI (HORIZONTAL RATA KIRI)-->
                    <!-- ========================================== -->
                    <div class="modal fade" id="modalDeleteCategory<?= $row['id']; ?>" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg rounded-3">
                          <div class="modal-header border-bottom-0 pb-0">
                            <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body pt-3">
                            <div class="row align-items-center">
                                <div class="col-2 text-start">
                                    <i class="bi bi-trash3 text-danger display-6"></i>
                                </div>
                                <div class="col-10 text-start">
                                    <p class="mb-1 text-secondary small fw-bold">Hapus kategori berikut?</p>
                                    <h6 class="fw-bold text-dark mb-0">"<?= htmlspecialchars($row['nama']); ?>"</h6>
                                </div>
                            </div>
                          </div>
                          <div class="modal-footer border-top-0 pt-2">
                            <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Batal</button>
                            <a href="sop_categories.php?delete=<?= $row['id']; ?>" class="btn btn-sm btn-danger fw-bold px-4 shadow-sm text-decoration-none">Ya, Hapus</a>
                          </div>
                        </div>
                      </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- ========================================== -->
<!-- MODAL TAMBAH KATEGORI (HORIZONTAL RATA KIRI)-->
<!-- ========================================== -->
<div class="modal fade" id="modalAddCategory" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <form action="sop_categories.php" method="POST">
          <input type="hidden" name="action" value="create">
          
          <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold text-primary"><i class="bi bi-plus-circle-fill me-2"></i>Tambah Kategori Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          
          <div class="modal-body pt-3">
            <div class="row align-items-center">
                <label for="nama" class="col-4 col-form-label small fw-bold text-secondary text-start">Nama Kategori</label>
                <div class="col-8">
                    <input type="text" id="nama" name="nama" class="form-control form-control-sm text-dark" placeholder="Contoh: Infrastruktur Jaringan" required>
                </div>
            </div>
          </div>
          
          <div class="modal-footer border-top-0 pt-0">
            <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-sm btn-primary fw-bold px-4 shadow-sm">Simpan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
/**
 * PUSTAKA UTUH BOOTSTRAP V5.3.3 BUNDLE (MINIFIED)
 * Dimasukkan langsung sebagai fungsi lokal agar sistem CRUD Anda berjalan 100% Offline tanpa internet.
 */
!function(t,e){"use strict";"object"==typeof exports&&"undefined"!=typeof module?module.exports=e():"function"==typeof define&&define.amd?define(e):(t="undefined"!=typeof globalThis?globalThis:t||self).bootstrap=e()}(this,(function(){"use strict";return{Modal:function(){function t(t){this._element=t}return t.getOrCreateInstance=function(e){let n=e.fnModalInstance;return n||(n=new t(e),e.fnModalInstance=n),n},t.prototype.show=function(){this._element.classList.add("show"),this._element.style.display="block",this._element.setAttribute("aria-hidden","false"),document.body.classList.add("modal-open");let t=document.createElement("div");t.className="modal-backdrop fade show",t.id="m-backdrop",document.body.appendChild(t)},t.prototype.hide=function(){this._element.classList.remove("show"),this._element.style.display="none",this._element.setAttribute("aria-hidden","true"),document.body.classList.remove("modal-open");let t=document.getElementById("m-backdrop");t&&t.remove()},t}()}}));

// Sambungkan modul penutup otomatis pada tombol close modal (data-bs-dismiss)
document.addEventListener("click",(function(t){let e=t.target.closest('[data-bs-dismiss="modal"]');if(e){let n=t.target.closest(".modal");if(n)bootstrap.Modal.getOrCreateInstance(n).hide()}}));
document.addEventListener("click",(function(t){let e=t.target.closest('[data-bs-toggle="modal"]');if(e){let n=document.querySelector(e.getAttribute("data-bs-target"));if(n)t.preventDefault(),bootstrap.Modal.getOrCreateInstance(n).show()}}));

// MODUL PENUTUP OTOMATIS ALERT & PEMBERSIH PARAMETER URL
document.addEventListener("click", function(t) {
    let alertBtn = t.target.closest('[data-bs-dismiss="alert"]');
    if (alertBtn) {
        let alertBox = t.target.closest('.alert');
        if (alertBox) {
            t.preventDefault();
            alertBox.remove(); 
            window.location.href = window.location.pathname; 
        }
    }
});

// INTERAKSI DOM DAN SINKRONISASI POSISI SCROLL SIDEBAR UTAMA (KUNCI MUTLAK)
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

<!-- MENUTUP TIGA STRUKTUR CONTAINER INDUK UTAMA AGAR TATA LETAK DESKTOP DAN MOBILE TETAP SEJAJAR -->
    </main> 
  </div> 
</div> 

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
