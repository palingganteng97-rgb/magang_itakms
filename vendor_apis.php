<?php
require_once __DIR__ . '/auth.php';
require_login();

// =========================================================================
// 1. AMBIL DATA ROLE USER DINAMIS & TERJEMAHKAN ID ANGKA MENJADI TEKS
// =========================================================================
$sessionRoleId = isset($_SESSION['user']['role_id']) ? (int)$_SESSION['user']['role_id'] : 4;

$roleMapping = [
    1 => 'Super Admin',
    2 => 'Admin IT',
    3 => 'Teknisi',
    4 => 'Viewer'
];

$userRole = isset($roleMapping[$sessionRoleId]) ? $roleMapping[$sessionRoleId] : 'Viewer';

// =========================================================================
// 2. PROTEKSI HALAMAN BERDASARKAN MATRIKS RESMI DOKUMEN ANDA
// =========================================================================
if ($userRole === 'Viewer') {
    echo "<script>
            alert('Akses Ditolak! Akun Viewer tidak memiliki izin untuk melihat data Integrasi API Vendor.');
            window.location='vendors.php';
          </script>";
    exit();
}

// =========================================================================
// FIX PENYEBAB ERROR: DEKLARASI FUNGSI CEK HAK AKSES CRUD UNTUK VENDOR APIS
// =========================================================================
if (!function_exists('hasCrudAccess')) {
    function hasCrudAccess($fileName, $actionType, $currentRole) {
        $crudMatrix = [
            'vendor_apis.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 
                'Admin IT'    => ['C', 'R', 'U', 'D'], 
                'Teknisi'     => ['R'], 
                'Viewer'      => []
            ]
        ];

        $cleanFileName = basename($fileName);
        if ($cleanFileName === 'vendor_apis.php') {
            $fileName = 'vendor_apis.php';
        }

        if (isset($crudMatrix[$fileName][$currentRole])) {
            return in_array($actionType, $crudMatrix[$fileName][$currentRole]);
        }
        return false;
    }
}

// =========================================================================
// 3. KONFIGURASI DATABASE
// =========================================================================
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

$vendor_id = isset($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : 0;

if ($vendor_id <= 0) {
    header("Location: vendors.php");
    exit();
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // =========================================================================
    // 4. LOGIKA PEMROSESAN CRUD (CREATE, UPDATE, DELETE)
    // =========================================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];

        // -----------------------------------------------------------------
        // PROSES TAMBAH DATA (CREATE)
        // -----------------------------------------------------------------
        if ($action === 'create') {
            if (!hasCrudAccess('vendor_apis.php', 'C', $userRole)) {
                die("Akses Ditolak: Anda tidak memiliki izin untuk menambah data.");
            }

            $sqlInsert = "INSERT INTO vendor_apis (vendor_id, nama_api, environment, base_url, client_id, client_secret, user_key, secret_key, dokumentasi) 
                          VALUES (:vendor_id, :nama_api, :environment, :base_url, :client_id, :client_secret, :user_key, :secret_key, :dokumentasi)";
            
            $stmtInsert = $conn->prepare($sqlInsert);
            $stmtInsert->execute([
                ':vendor_id'     => $vendor_id,
                ':nama_api'      => !empty($_POST['nama_api']) ? $_POST['nama_api'] : null,
                ':environment'   => isset($_POST['environment']) ? (int)$_POST['environment'] : null,
                ':base_url'      => !empty($_POST['base_url']) ? $_POST['base_url'] : null,
                ':client_id'     => !empty($_POST['client_id']) ? $_POST['client_id'] : null,
                ':client_secret' => !empty($_POST['client_secret']) ? $_POST['client_secret'] : null,
                ':user_key'      => !empty($_POST['user_key']) ? $_POST['user_key'] : null,
                ':secret_key'    => !empty($_POST['secret_key']) ? $_POST['secret_key'] : null,
                ':dokumentasi'   => !empty($_POST['dokumentasi']) ? $_POST['dokumentasi'] : null
            ]);

            echo "<script>alert('Data API berhasil ditambahkan!'); window.location='vendor_apis.php?vendor_id=$vendor_id';</script>";
            exit();
        }

        // -----------------------------------------------------------------
        // PROSES UBAH DATA (UPDATE)
        // -----------------------------------------------------------------
        if ($action === 'update') {
            if (!hasCrudAccess('vendor_apis.php', 'U', $userRole)) {
                die("Akses Ditolak: Anda tidak memiliki izin untuk mengubah data.");
            }

            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            $sqlUpdate = "UPDATE vendor_apis SET 
                            nama_api = :nama_api, 
                            environment = :environment, 
                            base_url = :base_url, 
                            client_id = :client_id, 
                            client_secret = :client_secret, 
                            user_key = :user_key, 
                            secret_key = :secret_key, 
                            dokumentasi = :dokumentasi 
                          WHERE id = :id AND vendor_id = :vendor_id";
            
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->execute([
                ':id'            => $id,
                ':vendor_id'     => $vendor_id,
                ':nama_api'      => !empty($_POST['nama_api']) ? $_POST['nama_api'] : null,
                ':environment'   => isset($_POST['environment']) ? (int)$_POST['environment'] : null,
                ':base_url'      => !empty($_POST['base_url']) ? $_POST['base_url'] : null,
                ':client_id'     => !empty($_POST['client_id']) ? $_POST['client_id'] : null,
                ':client_secret' => !empty($_POST['client_secret']) ? $_POST['client_secret'] : null,
                ':user_key'      => !empty($_POST['user_key']) ? $_POST['user_key'] : null,
                ':secret_key'    => !empty($_POST['secret_key']) ? $_POST['secret_key'] : null,
                ':dokumentasi'   => !empty($_POST['dokumentasi']) ? $_POST['dokumentasi'] : null
            ]);

            echo "<script>alert('Data API berhasil diperbarui!'); window.location='vendor_apis.php?vendor_id=$vendor_id';</script>";
            exit();
        }

        // -----------------------------------------------------------------
        // PROSES HAPUS DATA (DELETE)
        // -----------------------------------------------------------------
        if ($action === 'delete') {
            if (!hasCrudAccess('vendor_apis.php', 'D', $userRole)) {
                die("Akses Ditolak: Anda tidak memiliki izin untuk menghapus data.");
            }

            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            $sqlDelete = "DELETE FROM vendor_apis WHERE id = :id AND vendor_id = :vendor_id";
            $stmtDelete = $conn->prepare($sqlDelete);
            $stmtDelete->execute([
                ':id'        => $id,
                ':vendor_id' => $vendor_id
            ]);

            echo "<script>alert('Data API berhasil dihapus!'); window.location='vendor_apis.php?vendor_id=$vendor_id';</script>";
            exit();
        }
    }

    // =========================================================================
    // 5. PENARIKAN DATA UNTUK VIEW (READ)
    // =========================================================================
    // Ambal Informasi Nama Vendor Utama untuk komponen Header
    $vendorSql = "SELECT nama FROM vendors WHERE id = :vendor_id";
    $vendorStmt = $conn->prepare($vendorSql);
    $vendorStmt->execute([':vendor_id' => $vendor_id]);
    $vendorMain = $vendorStmt->fetch(PDO::FETCH_ASSOC);

    if (!$vendorMain) {
        die("Data vendor utama tidak ditemukan di database.");
    }

    // Ambil Semua daftar Data API Berdasarkan vendor_id
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
</head>    
<body>

<main class="col-12 px-2 px-md-4 pt-4" style="min-width: 0; overflow: hidden;">

  <!-- Header Main Konten Atas Vendor APIs -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center pt-3 pb-2 mb-3 border-bottom gap-2">
    <div class="w-100">
      <!-- Tombol kembali ke vendors.php -->
      <div class="mb-2">
        <a href="vendors.php" class="btn btn-sm btn-outline-secondary rounded-3 px-3 py-1 d-inline-flex align-items-center gap-2 small text-decoration-none">
          <i class="bi bi-arrow-left"></i> Kembali ke Vendor
        </a>
      </div>
      <div>
        <h1 class="h4 h3-md fw-bold text-dark mb-1 text-break">Integrasi API Vendor</h1>
        <p class="text-muted small mb-0 d-none d-sm-block">Mengelola kredensial endpoint, token parameter, dan berkas dokumentasi API dari pihak vendor.</p>
      </div>
    </div>
  </div>

  <!-- Notifikasi Flash Status CRUD Vendor APIs -->
  <?php if(isset($_GET['status'])): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mx-0" role="alert">
        <?php
          if($_GET['status'] == 'success_add') echo '<i class="bi bi-check-circle-fill me-2"></i> Konfigurasi API baru berhasil didaftarkan!';
          if($_GET['status'] == 'success_update') echo '<i class="bi bi-check-circle-fill me-2"></i> Konfigurasi API berhasil diperbarui!';
          if($_GET['status'] == 'success_delete') echo '<i class="bi bi-trash-fill me-2"></i> Konfigurasi API berhasil dihapus dari sistem!';
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Card Wadah Tabel Integrasi API -->
  <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4 bg-white p-2 p-md-3">
    
    <!-- Bagian Atas Tabel: Judul & Tombol Tambah API -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center flex-wrap gap-2 mb-3 mb-md-4">
      <h5 class="mb-0 text-dark fw-bold d-flex align-items-center text-break" style="font-size: calc(1rem + 0.2vw);">
        <i class="bi bi-cloud-slash-fill me-2 text-primary"></i> Daftar Endpoint API: <span class="text-primary ms-1"><?= htmlspecialchars($vendorMain['nama']); ?></span>
      </h5>
      
      <?php if (hasCrudAccess('vendor_apis.php', 'C', $userRole)): ?>
        <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 py-2 py-sm-1 d-flex align-items-center justify-content-center gap-2 shadow-sm w-100 w-sm-auto" data-bs-toggle="modal" data-bs-target="#modalTambahApi">
            <i class="bi bi-plus-circle"></i> Tambah API Baru
        </button>
      <?php endif; ?>
    </div>

        <!-- Tabel Data Endpoint -->
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 px-2" style="min-width: 900px;">
              <thead class="table-light text-uppercase font-monospace small border-top">
                <tr>
                  <th class="ps-4 py-3" width="5%">No</th>
                  <th width="20%">Nama API</th>
                  <th width="12%">Environment</th>
                  <th width="25%">Base URL</th>
                  <th width="25%">Kredensial / Keys</th>
                  <th class="text-center pe-4" width="13%">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($apis)): ?>
                  <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                      <i class="bi bi-folder-x display-6 mb-2 d-block text-secondary"></i>
                      Belum ada data konfigurasi API untuk vendor ini.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php $no = 1; foreach ($apis as $api): ?>
                    <tr>
                      <td class="ps-4 fw-medium text-secondary"><?= $no++; ?></td>
                      <td>
                        <div class="fw-bold text-dark text-break"><?= htmlspecialchars($api['nama_api']); ?></div>
                        <?php if (!empty($api['dokumentasi'])): ?>
                          <a href="<?= htmlspecialchars($api['dokumentasi']); ?>" target="_blank" class="btn btn-sm btn-link text-decoration-none p-0 mt-1 d-inline-flex align-items-center gap-1 small fw-medium">
                            <i class="bi bi-journal-bookmark"></i> Dokumen API <i class="bi bi-box-arrow-up-right small"></i>
                          </a>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($api['environment'] == 1): ?>
                          <span class="badge bg-warning-subtle text-warning border border-warning rounded-pill px-2.5">Sandbox / Dev</span>
                        <?php else: ?>
                          <span class="badge bg-success-subtle text-success border border-success rounded-pill px-2.5">Production</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <div class="bg-light px-2 py-1.5 rounded-3 text-break border font-monospace text-secondary small">
                          <?= htmlspecialchars($api['base_url'] ?? '-'); ?>
                        </div>
                      </td>
                      <td>
                        <div class="font-monospace small">
                          <?php if (!empty($api['client_id'])): ?>
                            <div class="text-muted"><span class="fw-semibold text-secondary">ID:</span> <?= htmlspecialchars($api['client_id']); ?></div>
                          <?php endif; ?>
                          <?php if (!empty($api['client_secret'])): ?>
                            <div class="text-muted"><span class="fw-semibold text-secondary">Sec:</span> <?= htmlspecialchars(mb_strimwidth($api['client_secret'], 0, 15, '...')); ?></div>
                          <?php endif; ?>
                          <?php if (!empty($api['user_key']) || !empty($api['secret_key'])): ?>
                            <div class="text-primary-emphasis mt-0.5"><i class="bi bi-key-fill small"></i> Custom Keys Ready</div>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td class="text-center pe-4">
                        <div class="d-inline-flex gap-1">
                          <!-- Tombol Detail -->
                          <button type="button" class="btn btn-sm btn-outline-secondary rounded-3" title="Detail Lengkap" data-bs-toggle="modal" data-bs-target="#modalDetailApi<?= $api['id']; ?>">
                            <i class="bi bi-eye"></i>
                          </button>
                          
                          <!-- Tombol Edit -->
                          <?php if (hasCrudAccess('vendor_apis.php', 'U', $userRole)): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-3" title="Ubah Data" data-bs-toggle="modal" data-bs-target="#modalEditApi<?= $api['id']; ?>">
                              <i class="bi bi-pencil-square"></i>
                            </button>
                          <?php endif; ?>

                          <!-- Tombol Hapus -->
                          <?php if (hasCrudAccess('vendor_apis.php', 'D', $userRole)): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-3" title="Hapus Data" data-bs-toggle="modal" data-bs-target="#modalHapusApi<?= $api['id']; ?>">
                              <i class="bi bi-trash"></i>
                            </button>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

  </div> <!-- Penutup container-fluid -->
</div> <!-- Penutup main-content -->

<!-- =========================================================================
     MODAL TAMBAH API NEW REGISTRATION (CREATE)
     ========================================================================= -->
<?php if (hasCrudAccess('vendor_apis.php', 'C', $userRole)): ?>
  <div class="modal fade" id="modalTambahApi" tabindex="-1" aria-labelledby="labelModalTambah" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 shadow rounded-4">
        
        <form action="" method="POST">
          <!-- Parameter wajib untuk identifikasi proses Create di Backend PHP -->
          <input type="hidden" name="action" value="create">
          
          <div class="modal-header border-bottom-0 py-3 px-4">
            <h5 class="modal-title fw-bold text-dark" id="labelModalTambah">
              <i class="bi bi-plus-circle text-primary me-2"></i>Registrasi API Baru
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          
          <div class="modal-body p-4 pt-0">
            <div class="row g-3">
              
              <!-- Nama API -->
              <div class="col-md-8">
                <label class="form-label small fw-bold text-secondary">Nama API <span class="text-danger">*</span></label>
                <input type="text" class="form-control rounded-3" name="nama_api" placeholder="Contoh: API Gateway Verifikasi" required maxlength="150">
              </div>
              
              <!-- Environment -->
              <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Environment <span class="text-danger">*</span></label>
                <select class="form-select rounded-3" name="environment" required>
                  <option value="1" selected>Sandbox / Dev</option>
                  <option value="2">Production</option>
                </select>
              </div>
              
              <!-- Base URL -->
              <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Base URL</label>
                <input type="url" class="form-control rounded-3 font-monospace" name="base_url" placeholder="https://vendor.com" maxlength="255">
              </div>
              
              <!-- Client ID -->
              <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Client ID</label>
                <input type="text" class="form-control rounded-3 font-monospace" name="client_id" placeholder="Masukkan Client ID" maxlength="255">
              </div>
              
              <!-- Link Dokumentasi -->
              <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Link Dokumentasi</label>
                <input type="text" class="form-control rounded-3" name="dokumentasi" placeholder="https://vendor.com" maxlength="255">
              </div>
              
              <!-- Client Secret -->
              <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Client Secret</label>
                <textarea class="form-control rounded-3 font-monospace" name="client_secret" placeholder="Masukkan Client Secret" rows="2"></textarea>
              </div>
              
              <!-- User Key -->
              <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">User Key</label>
                <textarea class="form-control rounded-3 font-monospace" name="user_key" placeholder="Masukkan User Key (jika ada)" rows="2"></textarea>
              </div>
              
              <!-- Secret Key -->
              <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Secret Key</label>
                <textarea class="form-control rounded-3 font-monospace" name="secret_key" placeholder="Masukkan Secret Key (jika ada)" rows="2"></textarea>
              </div>
              
            </div>
          </div>
          
          <div class="modal-footer border-top-0 px-4 pb-4">
            <button type="button" class="btn btn-secondary rounded-3 px-3" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary rounded-3 px-4">Simpan API</button>
          </div>
        </form>
        
      </div>
    </div>
  </div>
<?php endif; ?>
<!-- =========================================================================
     MODAL EDIT & HAPUS API (DILAKUKAN DI LUAR TABEL VIA LOOPING DATA)
     ========================================================================= -->
<?php if (!empty($apis)): ?>
  <?php foreach ($apis as $api): ?>

    <!-- 1. MODAL EDIT DATA (UPDATE) -->
    <?php if (hasCrudAccess('vendor_apis.php', 'U', $userRole)): ?>
      <div class="modal fade" id="modalEditApi<?= $api['id']; ?>" tabindex="-1" aria-labelledby="labelModalEdit<?= $api['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content border-0 shadow rounded-4">
            <form action="" method="POST">
              <!-- Parameter wajib untuk identifikasi proses Update di Backend PHP -->
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?= $api['id']; ?>">
              
              <div class="modal-header border-bottom-0 py-3 px-4">
                <h5 class="modal-title fw-bold text-dark" id="labelModalEdit<?= $api['id']; ?>">
                  <i class="bi bi-pencil-square text-primary me-2"></i>Ubah Konfigurasi API
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              
              <div class="modal-body p-4 pt-0">
                <div class="row g-3">
                  <!-- Nama API -->
                  <div class="col-md-8">
                    <label class="form-label small fw-bold text-secondary">Nama API <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-3" name="nama_api" value="<?= htmlspecialchars($api['nama_api']); ?>" required maxlength="150">
                  </div>
                  
                  <!-- Environment -->
                  <div class="col-md-4">
                    <label class="form-label small fw-bold text-secondary">Environment <span class="text-danger">*</span></label>
                    <select class="form-select rounded-3" name="environment" required>
                      <option value="1" <?= $api['environment'] == 1 ? 'selected' : ''; ?>>Sandbox / Dev</option>
                      <option value="2" <?= $api['environment'] == 2 ? 'selected' : ''; ?>>Production</option>
                    </select>
                  </div>
                  
                  <!-- Base URL -->
                  <div class="col-12">
                    <label class="form-label small fw-bold text-secondary">Base URL</label>
                    <input type="url" class="form-control rounded-3 font-monospace" name="base_url" value="<?= htmlspecialchars($api['base_url'] ?? ''); ?>" placeholder="https://vendor.com" maxlength="255">
                  </div>
                  
                  <!-- Client ID -->
                  <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">Client ID</label>
                    <input type="text" class="form-control rounded-3 font-monospace" name="client_id" value="<?= htmlspecialchars($api['client_id'] ?? ''); ?>" maxlength="255">
                  </div>
                  
                  <!-- Link Dokumentasi -->
                  <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">Link Dokumentasi</label>
                    <input type="text" class="form-control rounded-3" name="dokumentasi" value="<?= htmlspecialchars($api['dokumentasi'] ?? ''); ?>" placeholder="https://vendor.com" maxlength="255">
                  </div>
                  
                  <!-- Client Secret -->
                  <div class="col-12">
                    <label class="form-label small fw-bold text-secondary">Client Secret</label>
                    <textarea class="form-control rounded-3 font-monospace" name="client_secret" rows="2"><?= htmlspecialchars($api['client_secret'] ?? ''); ?></textarea>
                  </div>
                  
                  <!-- User Key -->
                  <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">User Key</label>
                    <textarea class="form-control rounded-3 font-monospace" name="user_key" rows="2"><?= htmlspecialchars($api['user_key'] ?? ''); ?></textarea>
                  </div>
                  
                  <!-- Secret Key -->
                  <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">Secret Key</label>
                    <textarea class="form-control rounded-3 font-monospace" name="secret_key" rows="2"><?= htmlspecialchars($api['secret_key'] ?? ''); ?></textarea>
                  </div>
                </div>
              </div>
              
              <div class="modal-footer border-top-0 px-4 pb-4">
                <button type="button" class="btn btn-secondary rounded-3 px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary rounded-3 px-4">Simpan Perubahan</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- 2. MODAL KONFIRMASI HAPUS DATA (DELETE) -->
    <?php if (hasCrudAccess('vendor_apis.php', 'D', $userRole)): ?>
      <div class="modal fade" id="modalHapusApi<?= $api['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
          <div class="modal-content border-0 shadow rounded-4">
            <form action="" method="POST">
              <!-- Parameter wajib untuk identifikasi proses Delete di Backend PHP -->
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $api['id']; ?>">
              
              <div class="modal-body p-4 text-center">
                <i class="bi bi-exclamation-triangle text-danger display-5 mb-3 d-block"></i>
                <h6 class="fw-bold text-dark mb-2">Hapus Konfigurasi API?</h6>
                <p class="text-muted small mb-0">Tindakan ini tidak bisa dibatalkan. Menghapus API: <br><strong class="text-danger"><?= htmlspecialchars($api['nama_api']); ?></strong>.</p>
              </div>
              
              <div class="modal-footer border-top-0 px-4 pb-4 justify-content-center gap-2">
                <button type="button" class="btn btn-sm btn-secondary rounded-3 px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-danger rounded-3 px-3">Ya, Hapus</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>

  <?php endforeach; ?>
<?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

  </div> <!-- Penutup container-fluid -->
</div> <!-- Penutup main-content -->

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
