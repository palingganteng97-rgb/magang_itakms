<?php
require_once __DIR__ . '/auth.php';
require_login();

// =========================================================================
// AMBIL DATA ROLE USER DINAMIS DARI DATABASE SESSION (Kunci Utama Hak Akses)
// =========================================================================
$userRole = isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : 'Viewer';

// =========================================================================
// PROTEKSI HALAMAN BACKEND: Akun Viewer bertanda 'X' (Akses Dilarang Total!)
// =========================================================================
if ($userRole === 'Viewer') {
    echo "<script>
            alert('Akses Ditolak! Akun Viewer tidak memiliki izin untuk melihat data Integrasi API Vendor.');
            window.location='dashboard.php';
          </script>";
    exit();
}

// 1. Konfigurasi Database
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

// Mengambil ID Vendor utama dari parameter URL (misal: vendor_apis.php?vendor_id=1)
$vendor_id = isset($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : 0;

if ($vendor_id <= 0) {
    header("Location: vendors.php");
    exit;
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Ambil Informasi Nama Vendor Utama untuk Header
    $vendorSql = "SELECT nama FROM vendors WHERE id = :vendor_id";
    $vendorStmt = $conn->prepare($vendorSql);
    $vendorStmt->execute([':vendor_id' => $vendor_id]);
    $vendorMain = $vendorStmt->fetch(PDO::FETCH_ASSOC);

    if (!$vendorMain) {
        die("Data vendor utama tidak ditemukan.");
    }

    // 3. Query Mengambil Semua Data API Berdasarkan vendor_id
    $sql = "SELECT * FROM vendor_apis WHERE vendor_id = :vendor_id ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':vendor_id' => $vendor_id]);
    $apis = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
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

    <!-- AREA KONTEN UTAMA VENDOR APIS -->
    <main class="col-12" style="min-width: 0; overflow: hidden;">
      
      <!-- Header Halaman -->
      <!-- PERUBAHAN: Menggunakan flex-column agar di mobile bertumpuk rapi, dan kembali flex-sm-row di desktop -->
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center pt-2 pb-2 mb-3 border-bottom gap-2">
        <div class="w-100">
          <!-- Tombol Kembali ke Halaman Vendors Utama -->
          <div class="mb-2">
            <a href="vendors.php" class="btn btn-sm btn-outline-secondary rounded-3 px-3 py-1 d-inline-flex align-items-center gap-2 small">
              <i class="bi bi-arrow-left"></i> Kembali ke Vendor
            </a>
          </div>
          <!-- PERUBAHAN: Tambah class responsive-title -->
          <h1 class="h4 h3-md fw-bold text-dark mb-1 text-break responsive-title">Integrasi API Vendor</h1>
          <p class="text-muted small mb-0 d-none d-sm-block">Mengelola kredensial endpoint, token parameter, dan berkas dokumentasi API dari pihak vendor.</p>
        </div>
      </div>

      <!-- Flash Message Form CRUD -->
      <?php if(isset($_GET['status'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 mx-0" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Aksi database berhasil diproses!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Wadah Card Tabel -->
      <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4 bg-white p-2 p-md-3">
        
        <!-- Bagian Atas Tabel: Judul & Tombol Tambah -->
        <!-- PERUBAHAN: flex-column di mobile menjamin tombol tambah melompat ke bawah dengan rapi jika judul terlalu panjang -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3 mb-md-4">
          <h5 class="mb-0 text-dark fw-bold d-flex align-items-center text-break responsive-subtitle">
            <i class="bi bi-code-slash me-2 text-purple" style="color: #6f42c1;"></i> 
            Daftar API: <span class="text-primary ms-1"><?= htmlspecialchars($vendorMain['nama']); ?></span>
          </h5>
          
          <!-- Fleksibel Otomatis: Hanya tampil jika Role memiliki izin Create ('C') di file ini -->
          <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'C', $userRole)): ?>
          <!-- PERUBAHAN: w-100 di mobile agar tombol penuh mudah ditekan jempol, w-auto di desktop -->
          <button type="button" class="btn btn-primary btn-sm text-white rounded-3 px-3 py-2 py-sm-1 shadow-sm d-flex align-items-center justify-content-center gap-2 w-100 w-sm-auto ms-0" style="background-color: #6f42c1; border-color: #6f42c1;" data-bs-toggle="modal" data-bs-target="#modalAddApi">
              <i class="bi bi-plus-lg"></i> Tambah API
          </button>
          <?php endif; ?>
          
        </div>

        <!-- PERUBAHAN: Penegasan properti pembungkus agar container tabel tidak merusak grid luar col-12 -->
        <div class="table-responsive w-100 rounded-3 border" style="overflow-x: auto; -webkit-overflow-scrolling: touch; display: block;">
          <table class="table table-striped table-hover align-middle mb-0 text-nowrap w-100">
            <thead class="table-light border-bottom">
              <tr>
                <th class="ps-3" style="width: 60px;">No</th>
                <th>Nama API</th>
                <th>Environment</th>
                <th>Base URL Endpoint</th>
                <th>Client Credentials (ID & Secret)</th>
                <th>API Keys (User & Secret)</th>
                <th>Dokumentasi</th>
                <th class="text-center pe-3" style="width: 120px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <!-- Isi looping data <tr> Anda diletakkan di sini -->
              <tr>
                <td colspan="8" class="text-center text-muted py-5" style="white-space: normal;">
                  <i class="bi bi-code-square display-4 d-block mb-3 text-secondary opacity-50"></i>
                  <span class="d-block fw-semibold text-dark mb-1">Belum Ada Jalur API Terdaftar</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div> <!-- /.table-responsive -->

      </div> <!-- /.card -->
    </main>

  </div> <!-- /.row -->
</div> <!-- /.container-fluid -->

<!-- PERUBAHAN: Mengganti modal-lg menjadi modal-xl agar ruang horizontal lebih luas -->
<div class="modal fade" id="modalAddApi" tabindex="-1" aria-labelledby="modalAddApiLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-bottom p-3">
        <h5 class="modal-title fw-bold text-dark" id="modalAddApiLabel">
          <i class="bi bi-code-slash me-2 text-purple" style="color: #6f42c1;"></i> Registrasi Konfigurasi API Baru
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="proses_vendor_api.php?action=add" method="POST">
        <div class="modal-body p-4">
          <!-- Hidden Input untuk Melempar ID Vendor -->
          <input type="hidden" name="vendor_id" value="<?= $vendor_id; ?>">

          <!-- Grid Pembagian 3 Kolom Sejajar (Menyamping ke Kanan) -->
          <div class="row g-4">
            
            <!-- KOLOM 1 (KIRI): INFORMASI UTAMA ENDPOINT -->
            <div class="col-md-4">
              <div class="mb-2 text-primary fw-bold small uppercase tracking-wider border-bottom pb-2">
                <i class="bi bi-globe me-1"></i> 1. Informasi Endpoint
              </div>
              <div class="p-3 bg-light rounded-3 border border-light-subtle h-100">
                <div class="mb-3">
                  <label class="form-label small fw-bold text-muted mb-1">Nama / Fungsi API</label>
                  <input type="text" class="form-control rounded-3" name="nama_api" placeholder="Contoh: API Cek Resi Logistik" required maxlength="150">
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-bold text-muted mb-1">Environment</label>
                  <select class="form-select rounded-3" name="environment" required>
                    <option value="1" selected>Development / Sandbox</option>
                    <option value="2">Production / Live</option>
                  </select>
                </div>
                <div>
                  <label class="form-label small fw-bold text-muted mb-1">Base URL Endpoint</label>
                  <input type="url" class="form-control rounded-3 font-monospace small text-primary" name="base_url" placeholder="https://vendor.com" required maxlength="255">
                </div>
              </div>
            </div>

            <!-- KOLOM 2 (TENGAH): KREDENSIAL OTENTIKASI -->
            <div class="col-md-5">
              <div class="mb-2 text-purple fw-bold small uppercase tracking-wider border-bottom pb-2" style="color: #6f42c1;">
                <i class="bi bi-shield-lock me-1"></i> 2. Kredensial & Autentikasi
              </div>
              <div class="p-3 bg-light rounded-3 border border-light-subtle h-100">
                <div class="row g-3">
                  <div class="col-6">
                    <label class="form-label small fw-bold text-muted mb-1">Client ID</label>
                    <input type="text" class="form-control rounded-3" name="client_id" placeholder="Masukkan Client ID" maxlength="255">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-bold text-muted mb-1">Client Secret</label>
                    <input type="text" class="form-control rounded-3" name="client_secret" placeholder="Masukkan Client Secret">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-bold text-muted mb-1">User Key</label>
                    <input type="text" class="form-control rounded-3" name="user_key" placeholder="Masukkan User Key">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-bold text-muted mb-1">Secret Key</label>
                    <input type="text" class="form-control rounded-3" name="secret_key" placeholder="Masukkan Secret Key">
                  </div>
                </div>
              </div>
            </div>

            <!-- KOLOM 3 (KANAN): DOKUMENTASI -->
            <div class="col-md-3">
              <div class="mb-2 text-secondary fw-bold small uppercase tracking-wider border-bottom pb-2">
                <i class="bi bi-file-earmark-text me-1"></i> 3. Referensi
              </div>
              <div class="p-3 bg-light rounded-3 border border-light-subtle h-100">
                <div>
                  <label class="form-label small fw-bold text-muted mb-1">Link Dokumentasi / Swagger URL</label>
                  <textarea class="form-control rounded-3 font-monospace small" name="dokumentasi" rows="5" placeholder="https://vendor.com" style="resize: none;" maxlength="255"></textarea>
                </div>
              </div>
            </div>

          </div> <!-- /.row -->

        </div>
        <div class="modal-footer bg-light border-top p-3 rounded-bottom-4">
          <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm text-white rounded-3 px-4 shadow-sm" style="background-color: #6f42c1;">Simpan Integrasi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- PERUBAHAN: Mengganti modal-lg menjadi modal-xl agar ruang horizontal lebih luas -->
<div class="modal fade" id="modalEditApi" tabindex="-1" aria-labelledby="modalEditApiLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-bottom p-3">
        <h5 class="modal-title fw-bold text-dark" id="modalEditApiLabel">
          <i class="bi bi-pencil-square me-2 text-warning"></i> Perbarui Konfigurasi API
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="proses_vendor_api.php?action=update" method="POST">
        <div class="modal-body p-4">
          <!-- Hidden Primary Key ID Data API -->
          <input type="hidden" name="id" id="edit_id">
          <input type="hidden" name="vendor_id" value="<?= $vendor_id; ?>">

          <!-- Grid Pembagian 3 Kolom Sejajar (Menyamping ke Kanan) -->
          <div class="row g-4">
            
            <!-- KOLOM 1 (KIRI): INFORMASI UTAMA ENDPOINT -->
            <div class="col-md-4">
              <div class="mb-2 text-primary fw-bold small uppercase tracking-wider border-bottom pb-2">
                <i class="bi bi-globe me-1"></i> 1. Informasi Endpoint
              </div>
              <div class="p-3 bg-light rounded-3 border border-light-subtle h-100">
                <div class="mb-3">
                  <label class="form-label small fw-bold text-muted mb-1">Nama / Fungsi API</label>
                  <input type="text" class="form-control rounded-3" name="nama_api" id="edit_nama_api" required maxlength="150">
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-bold text-muted mb-1">Environment</label>
                  <select class="form-select rounded-3" name="environment" id="edit_environment" required>
                    <option value="1">Development / Sandbox</option>
                    <option value="2">Production / Live</option>
                  </select>
                </div>
                <div>
                  <label class="form-label small fw-bold text-muted mb-1">Base URL Endpoint</label>
                  <input type="url" class="form-control rounded-3 font-monospace small text-primary" name="base_url" id="edit_base_url" required maxlength="255">
                </div>
              </div>
            </div>

            <!-- KOLOM 2 (TENGAH): KREDENSIAL OTENTIKASI -->
            <div class="col-md-5">
              <div class="mb-2 text-purple fw-bold small uppercase tracking-wider border-bottom pb-2" style="color: #6f42c1;">
                <i class="bi bi-shield-lock me-1"></i> 2. Kredensial & Autentikasi
              </div>
              <div class="p-3 bg-light rounded-3 border border-light-subtle h-100">
                <div class="row g-3">
                  <div class="col-6">
                    <label class="form-label small fw-bold text-muted mb-1">Client ID</label>
                    <input type="text" class="form-control rounded-3" name="client_id" id="edit_client_id" maxlength="255">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-bold text-muted mb-1">Client Secret</label>
                    <input type="text" class="form-control rounded-3" name="client_secret" id="edit_client_secret">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-bold text-muted mb-1">User Key</label>
                    <input type="text" class="form-control rounded-3" name="user_key" id="edit_user_key">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-bold text-muted mb-1">Secret Key</label>
                    <input type="text" class="form-control rounded-3" name="secret_key" id="edit_secret_key">
                  </div>
                </div>
              </div>
            </div>

            <!-- KOLOM 3 (KANAN): DOKUMENTASI -->
            <div class="col-md-3">
              <div class="mb-2 text-secondary fw-bold small uppercase tracking-wider border-bottom pb-2">
                <i class="bi bi-file-earmark-text me-1"></i> 3. Referensi
              </div>
              <div class="p-3 bg-light rounded-3 border border-light-subtle h-100">
                <div>
                  <label class="form-label small fw-bold text-muted mb-1">Link Dokumentasi / Swagger URL</label>
                  <textarea class="form-control rounded-3 font-monospace small" name="dokumentasi" id="edit_dokumentasi" rows="5" style="resize: none;" maxlength="255"></textarea>
                </div>
              </div>
            </div>

          </div> <!-- /.row -->

        </div>
        <div class="modal-footer bg-light border-top p-3 rounded-bottom-4">
          <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-warning rounded-3 px-4 shadow-sm fw-bold">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 3. JAVASCRIPT BINDING DATA UNTUK MODAL EDIT -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEditApi = document.getElementById('modalEditApi');
    if (modalEditApi) {
        modalEditApi.addEventListener('show.bs.modal', function (event) {
            // Tombol yang memicu modal terbuka
            const button = event.relatedTarget;
            
            // Ekstrak data dari atribut HTML data-*
            const id = button.getAttribute('data-id');
            const namaApi = button.getAttribute('data-nama_api');
            const env = button.getAttribute('data-environment');
            const baseUrl = button.getAttribute('data-base_url');
            const clientId = button.getAttribute('data-client_id');
            const clientSecret = button.getAttribute('data-client_secret');
            const userKey = button.getAttribute('data-user_key');
            const secretKey = button.getAttribute('data-secret_key');
            const dokumentasi = button.getAttribute('data-dokumentasi');

            // Isi nilai input form modal edit secara otomatis
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama_api').value = namaApi;
            document.getElementById('edit_environment').value = env;
            document.getElementById('edit_base_url').value = baseUrl;
            document.getElementById('edit_client_id').value = clientId;
            document.getElementById('edit_client_secret').value = clientSecret;
            document.getElementById('edit_user_key').value = userKey;
            document.getElementById('edit_secret_key').value = secretKey;
            document.getElementById('edit_dokumentasi').value = dokumentasi;
        });
    }
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
