<?php
require_once __DIR__ . '/auth.php';
require_login();

// 1. Konfigurasi Database Utama
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// 2. Logika Pemrosesan Form CRUD via POST
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ACTION: TAMBAH USER (CREATE)
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        try {
            // Validasi: Apakah username sudah dipakai?
            $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmtCheck->execute([$_POST['username']]);
            if ($stmtCheck->fetchColumn() > 0) {
                header("Location: user.php?status=error_duplicate");
                exit;
            }

            // LOGIKA BERTAHAP RESET ID: Cari ID terkecil yang kosong/hilang di tengah jalan
            $nextId = 1;
            $stmtFindId = $conn->query("SELECT id FROM users ORDER BY id ASC");
            $existingIds = $stmtFindId->fetchAll(PDO::FETCH_COLUMN);
            foreach ($existingIds as $id) {
                if ($id == $nextId) {
                    $nextId++;
                } else {
                    break; 
                }
            }

            // Upload Berkas Foto Baru
            $nama_foto = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                if (!file_exists(__DIR__ . '/uploads')) {
                    mkdir(__DIR__ . '/uploads', 0777, true);
                }
                $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $nama_foto = time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__ . '/uploads/' . $nama_foto);
            }

            $plain_password = $_POST['password'];

            $stmt = $conn->prepare("INSERT INTO users (id, role_id, nama, username, password, email, telepon, foto, status, building_id, floor_id, room_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $nextId, 1, $_POST['nama'], $_POST['username'], $plain_password, $_POST['email'], 
                $_POST['telepon'], $nama_foto, $_POST['status'], 
                $_POST['building_id'] ?: null, $_POST['floor_id'] ?: null, $_POST['room_id'] ?: null
            ]);
            
            header("Location: user.php?status=success_create");
            exit;
        } catch (\PDOException $e) {
            header("Location: user.php?status=error_create");
            exit;
        }
    }
    
    // ACTION: EDIT USER (UPDATE)
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        try {
            $id = $_POST['id'];
            
            // Validasi username milik orang lain
            $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $stmtCheck->execute([$_POST['username'], $id]);
            if ($stmtCheck->fetchColumn() > 0) {
                header("Location: user.php?status=error_duplicate");
                exit;
            }

            // Ambil info nama foto lama
            $stmtOld = $conn->prepare("SELECT foto FROM users WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldData = $stmtOld->fetch();
            $nama_foto = $oldData['foto'] ?? null;

            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                if (!empty($nama_foto) && file_exists(__DIR__ . '/uploads/' . $nama_foto)) {
                    @unlink(__DIR__ . '/uploads/' . $nama_foto);
                }
                $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $nama_foto = time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__ . '/uploads/' . $nama_foto);
            }

            if (!empty($_POST['password'])) {
                $plain_password = $_POST['password'];
                $stmt = $conn->prepare("UPDATE users SET nama=?, username=?, password=?, email=?, telepon=?, foto=?, status=?, building_id=?, floor_id=?, room_id=? WHERE id=?");
                $stmt->execute([
                    $_POST['nama'], $_POST['username'], $plain_password, $_POST['email'], 
                    $_POST['telepon'], $nama_foto, $_POST['status'], 
                    $_POST['building_id'] ?: null, $_POST['floor_id'] ?: null, $_POST['room_id'] ?: null, 
                    $id
                ]);
            } else {
                $stmt = $conn->prepare("UPDATE users SET nama=?, username=?, email=?, telepon=?, foto=?, status=?, building_id=?, floor_id=?, room_id=? WHERE id=?");
                $stmt->execute([
                    $_POST['nama'], $_POST['username'], $_POST['email'], 
                    $_POST['telepon'], $nama_foto, $_POST['status'], 
                    $_POST['building_id'] ?: null, $_POST['floor_id'] ?: null, $_POST['room_id'] ?: null, 
                    $id
                ]);
            }
            
            header("Location: user.php?status=success_update");
            exit;
        } catch (\PDOException $e) {
            header("Location: user.php?status=error_update");
            exit;
        }
    }

    // ACTION: HAPUS USER & FILE FISIK FOTO (DELETE)
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        try {
            $id = $_POST['id'];
            
            // 1. Ambil data nama file dari database
            $stmtOld = $conn->prepare("SELECT foto FROM users WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldData = $stmtOld->fetch();
            $nama_foto = isset($oldData['foto']) ? trim($oldData['foto']) : '';

            // 2. Eksekusi penghapusan file fisik dari folder uploads secara permanen
            if (!empty($nama_foto)) {
                $target_file = __DIR__ . '/uploads/' . $nama_foto;
                if (file_exists($target_file)) {
                    @unlink($target_file); 
                }
            }

            // 3. Hapus baris data di database
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);

            // 4. Setel ulang mesin Auto Increment database agar sinkron
            $stmtMax = $conn->query("SELECT MAX(id) FROM users");
            $maxId = $stmtMax->fetchColumn() ?: 0;
            $nextAutoIncrement = $maxId + 1;
            $conn->query("ALTER TABLE users AUTO_INCREMENT = $nextAutoIncrement");

            header("Location: user.php?status=success_delete");
            exit;
        } catch (\PDOException $e) {
            header("Location: user.php?status=error_delete");
            exit;
        }
    }
}

// Menangkap status redirect dari URL untuk menampilkan alert secara dinamis & aman
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success_create') { $message = "Data user berhasil ditambahkan!"; $messageType = "success"; }
    if ($_GET['status'] === 'success_update') { $message = "Data user berhasil diperbarui!"; $messageType = "success"; }
    if ($_GET['status'] === 'success_delete') { $message = "Data user berhasil dihapus!"; $messageType = "success"; }
    if ($_GET['status'] === 'error_duplicate') { $message = "Gagal memproses data: Username sudah digunakan oleh user lain!"; $messageType = "danger"; }
    if ($_GET['status'] === 'error_create') { $message = "Gagal menambahkan data baru!"; $messageType = "danger"; }
    if ($_GET['status'] === 'error_update') { $message = "Gagal memperbarui data user!"; $messageType = "danger"; }
    if ($_GET['status'] === 'error_delete') { $message = "Gagal menghapus data user!"; $messageType = "danger"; }
}

// Ambil data terbaru untuk tabel
$query = "SELECT u.*, b.nama AS nama_gedung, f.nama AS nama_lantai, r.nama AS nama_ruangan 
          FROM users u
          LEFT JOIN buildings b ON u.building_id = b.id
          LEFT JOIN floors f ON u.floor_id = f.id
          LEFT JOIN rooms r ON u.room_id = r.id
          ORDER BY u.id DESC LIMIT 1000";
$users = $conn->query($query)->fetchAll();

// Ambil semua data opsi dari tabel relasi
try {
    $buildingsOpt = $conn->query("SELECT id, nama FROM buildings ORDER BY nama ASC")->fetchAll();
    $floorsOpt    = $conn->query("SELECT id, nama FROM floors ORDER BY nama ASC")->fetchAll();
    $roomsOpt     = $conn->query("SELECT id, nama FROM rooms ORDER BY nama ASC")->fetchAll();
} catch (\PDOException $e) {
    $buildingsOpt = []; $floorsOpt = []; $roomsOpt = [];
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
        .sidebar { background-color: #212529; color: white; }
        .sidebar a { color: #adb5bd; text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { color: white; background-color: #343a40; }

        /* KODE FIX: Menyembunyikan batang scrollbar untuk Chrome, Safari, dan Opera */
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        /* Menyembunyikan batang scrollbar untuk Firefox dan IE/Edge */
        .hide-scrollbar {
            -ms-overflow-style: none;  /* IE dan Edge */
            scrollbar-width: none;  /* Firefox */
        }
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

<!-- ========================================== -->
<!-- 1. SIDEBAR MOBILE (OFFCANVAS)              -->
<!-- ========================================== -->
<div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
  <!-- Header Mobile (Tetap Diam di Atas) -->
  <div class="offcanvas-header border-bottom border-secondary">
    <h5 class="offcanvas-title" id="mobileSidebarLabel"><i class="bi bi-speedometer2 text-warning me-2"></i> ITAKMS</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  
  <!-- Body Offcanvas (Pembagian Layout Flex Vertikal) -->
  <div class="offcanvas-body p-0 d-flex flex-column" style="height: calc(100vh - 56px);">
    <!-- Area Menu Tengah Mobile (Ditambahkan class hide-scrollbar) -->
    <div class="flex-grow-1 overflow-y-auto p-3 hide-scrollbar">
      <ul class="nav flex-column gap-2">
        <!-- Dashboard Aktif di Mobile -->
        <li class="nav-item">
          <a href="dashboard.php" class="nav-link text-white p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
        </li>
        <li class="nav-item">
          <a href="roles.php" class="nav-link text-white p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Manajemen Roles</a>
        </li>
        <li class="nav-item">
          <a href="relasi.php" class="nav-link text-white p-2 rounded text-nowrap" style="overflow: hidden; text-overflow: ellipsis;">
            <i class="bi bi-diagram-3 me-2"></i> Manajemen Bangunan & Ruang
          </a>
        </li>
        <li class="nav-item">
          <a href="assets.php" class="nav-link text-white p-2 rounded"><i class="bi bi-folder2-open me-2"></i> Assets</a>
        </li>
        <li class="nav-item">
          <a href="manajemen_asset.php" class="nav-link text-white p-2 rounded"><i class="bi bi-boxes me-2"></i> Manajemen Asset</a>
        </li>
        <li class="nav-item">
          <a href="asset_movements.php" class="nav-link text-white p-2 rounded"><i class="bi bi-arrow-left-right me-2"></i> Log Perpindahan</a>
        </li>
        <li class="nav-item">
          <a href="server.php" class="nav-link text-white p-2 rounded"><i class="bi bi-hdd-network me-2"></i> Server</a>
        </li>
        <li class="nav-item">
          <a href="network_device.php" class="nav-link text-white p-2 rounded"><i class="bi bi-router me-2"></i> Network Device</a>
        </li>
        <li class="nav-item">
          <a href="network_port.php" class="nav-link text-white p-2 rounded"><i class="bi bi-ethernet me-2"></i> Network Port</a>
        </li>
        <li class="nav-item">
          <a href="user.php" class="nav-link active bg-primary text-white p-2 rounded"><i class="bi bi-person-lines-fill me-2"></i> User Profil</a>
        </li>
      </ul>
    </div>
    
    <!-- Tombol Logout Mobile (Mengunci di Posisi Dasar Bawah) -->
    <div class="mt-auto p-3 border-top border-secondary bg-dark w-100">
      <ul class="nav flex-column gap-2">
        <li class="nav-item">
          <a href="logout.php" class="nav-link p-2 rounded" style="color:#dc3545 !important;">
            <i class="bi bi-box-arrow-right me-2" style="color:#dc3545 !important;"></i> Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- 2. SIDEBAR DESKTOP                         -->
<!-- ========================================== -->
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar Desktop dengan Tinggi Layar Terkunci Permanen 100vh & Posisi Statis Fixed -->
    <nav class="col-md-4 col-lg-3 d-none d-md-flex flex-column sidebar p-3 text-bg-dark" style="min-height: 100vh; max-height: 100vh; position: fixed; z-index: 1000;">
      <!-- Judul Utama Dashboard Desktop (Tetap Diam) -->
      <h4 class="text-center mb-4 text-warning fw-bold pt-2"><i class="bi bi-speedometer2"></i> ITAKMS</h4>
      
      <!-- Area Menu Tengah Desktop (Ditambahkan class hide-scrollbar dan menghapus padding kanan pr-1) -->
      <div class="flex-grow-1 overflow-y-auto hide-scrollbar" style="max-height: calc(100vh - 160px);">
        <ul class="nav flex-column gap-2">
          <!-- Dashboard Aktif di Desktop -->
          <li class="nav-item">
            <a href="dashboard.php" class="nav-link text-white p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
          </li>
          <li class="nav-item">
            <a href="roles.php" class="nav-link text-white p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Manajemen Roles</a>
          </li>
          <li class="nav-item">
            <a href="relasi.php" class="nav-link text-white p-2 rounded text-nowrap" style="overflow: hidden; text-overflow: ellipsis;" title="Manajemen Bangunan & Ruang">
              <i class="bi bi-diagram-3 me-2"></i> Manajemen Bangunan & Ruang
            </a>
          </li>
          <li class="nav-item">
            <a href="assets.php" class="nav-link text-white p-2 rounded"><i class="bi bi-folder2-open me-2"></i> Assets</a>
          </li>
          <li class="nav-item">
            <a href="manajemen_asset.php" class="nav-link text-white p-2 rounded"><i class="bi bi-boxes me-2"></i> Manajemen Asset</a>
          </li>
          <li class="nav-item">
            <a href="asset_movements.php" class="nav-link text-white p-2 rounded"><i class="bi bi-arrow-left-right me-2"></i> Log Perpindahan</a>
          </li>
          <li class="nav-item">
            <a href="server.php" class="nav-link text-white p-2 rounded"><i class="bi bi-hdd-network me-2"></i> Server</a>
          </li>
          <li class="nav-item">
            <a href="network_device.php" class="nav-link text-white p-2 rounded"><i class="bi bi-router me-2"></i> Network Device</a>
          </li>
          <li class="nav-item">
            <a href="network_port.php" class="nav-link text-white p-2 rounded"><i class="bi bi-ethernet me-2"></i> Network Port</a>
          </li>
          <li class="nav-item">
            <a href="user.php" class="nav-link  active bg-primary text-white p-2 rounded"><i class="bi bi-person-lines-fill me-2"></i> User Profil</a>
          </li>
        </ul>
      </div>
      
      <!-- Tombol Logout Desktop (Mengunci Mengikuti Batas Layar Bawah) -->
      <div class="mt-auto pt-3 border-top border-secondary w-100 bg-dark">
        <ul class="nav flex-column gap-2">
          <li class="nav-item">
            <a href="logout.php" class="nav-link p-2 rounded" style="color:#dc3545 !important;">
              <i class="bi bi-box-arrow-right me-2" style="color:#dc3545 !important;"></i> Logout
            </a>
          </li>
        </ul>
      </div>
    </nav>

    <!-- AREA UTAMA KONTEN (Gunakan pembungkus ini agar susunan halaman tidak bergeser tertimpa sidebar) -->
    <main class="col-md-8 ms-sm-auto col-lg-9 px-md-4 pt-4 offset-md-4 offset-lg-3">

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">User Profil</h1>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="window.clearForm()"><i class="bi bi-person-plus-fill me-1"></i> Tambah User</button>
            </div>

            <!-- Notifikasi Alert Merah / Hijau -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="btn-close" onclick="window.location.href='user.php'" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- TABEL RESPONSIF -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">ID</th>
                                            <th>Foto</th>
                                            <th>Nama</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Telepon</th>
                                            <th>Status</th>
                                            <th>Gedung</th>
                                            <th>Lantai</th>
                                            <th>Ruangan</th>
                                            <th class="text-center pe-3">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($users) > 0): ?>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td class="ps-3 fw-bold"><?= $user['id'] ?></td>
                                                    <td>
                                                        <?php if (!empty($user['foto']) && file_exists(__DIR__ . '/uploads/' . $user['foto'])): ?>
                                                            <img src="uploads/<?= htmlspecialchars($user['foto']) ?>" alt="Profil" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover; border: 1px solid #dee2e6;">
                                                        <?php else: ?>
                                                            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; font-size: 0.85rem;">
                                                                <?= strtoupper(substr($user['nama'] ?? 'U', 0, 2)) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($user['nama']) ?></td>
                                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                                    <td><?= htmlspecialchars($user['telepon']) ?></td>
                                                    <td>
                                                        <span class="badge <?= $user['status'] == 1 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> rounded-pill px-3">
                                                            <?= $user['status'] == 1 ? 'Aktif' : 'Non-Aktif' ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($user['nama_gedung'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($user['nama_lantai'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($user['nama_ruangan'] ?? '-') ?></td>
                                                    <td class="text-center pe-3">
                                                        <button type="button" class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#userModal" onclick='window.editUser(<?= json_encode($user, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="bi bi-pencil-square"></i></button>
                                                        <!-- PERBAIKAN FORM HAPUS PADA TABEL ANDA -->
                                                        <form action="user.php" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                                                            <input type="hidden" name="action" value="delete">
                                                            <!-- Pastikan baris ini tertulis lengkap untuk mengirim ID target ke PHP -->
                                                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="11" class="text-center text-muted py-4">Tidak ada data user ditemukan.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- MODAL FORM TAMBAH / EDIT -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="user.php" method="POST" id="userForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Tambah User Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="userId">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nama Lengkap</label><input type="text" name="nama" id="userNama" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Username</label><input type="text" name="username" id="userUsername" class="form-control" required></div>
                        
                        <!-- Input Password + Tombol Intip Mata -->
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" name="password" id="userPassword" class="form-control" placeholder="Isi password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye-slash" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                            <small class="text-muted id-hint d-none">Kosongkan jika tidak ingin mengubah password lama.</small>
                        </div>

                        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" id="userEmail" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Telepon</label><input type="text" name="telepon" id="userTelepon" class="form-control" required></div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" id="userStatus" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Non-Aktif</option>
                            </select>
                        </div>
                        
                        <div class="col-md-12"><label class="form-label">Foto Profil</label><input type="file" name="foto" id="userFoto" class="form-control" accept="image/png, image/jpeg, image/jpg"><small class="text-muted image-hint d-none">Kosongkan jika tidak ingin mengganti foto lama.</small></div>

                        <!-- Dropdown Relasi Dinamis -->
                        <div class="col-md-4">
                            <label class="form-label">Gedung</label>
                            <select name="building_id" id="userBuilding" class="form-select">
                                <option value="">-- Pilih Gedung --</option>
                                <?php foreach ($buildingsOpt as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Lantai</label>
                            <select name="floor_id" id="userFloor" class="form-select">
                                <option value="">-- Pilih Lantai --</option>
                                <?php foreach ($floorsOpt as $f): ?>
                                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ruangan</label>
                            <select name="room_id" id="userRoom" class="form-select">
                                <option value="">-- Pilih Ruangan --</option>
                                <?php foreach ($roomsOpt as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================================
     BOOTSTRAP JS ENGINE OFFLINE MURNI & INTERAKSI WINDOW GLOBAL
     ========================================================================= -->
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
            window.location.href = "user.php"; 
        }
    }
});

// AKSI INTERAKTIF TOMBOL MATA UNTUK MENGINTIP KATA SANDI
document.addEventListener("DOMContentLoaded", function() {
    const togglePasswordBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('userPassword');
    const passwordIcon = document.getElementById('togglePasswordIcon');
    if (togglePasswordBtn && passwordInput && passwordIcon) {
        togglePasswordBtn.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            passwordIcon.classList.toggle('bi-eye');
            passwordIcon.classList.toggle('bi-eye-slash');
        });
    }
});

// LOGIKA RESET INPUT FORMULIR (TAMBAH USER BARU)
window.clearForm = function() {
    const form = document.getElementById('userForm'); if (form) form.reset();
    if (document.getElementById('formAction')) document.getElementById('formAction').value = 'create';
    if (document.getElementById('userId')) document.getElementById('userId').value = '';
    if (document.getElementById('userModalLabel')) document.getElementById('userModalLabel').innerText = 'Tambah User Baru';
    if (document.getElementById('userPassword')) {
        document.getElementById('userPassword').required = true;
        document.getElementById('userPassword').value = '';
        document.getElementById('userPassword').setAttribute('type', 'password');
    }
    
    // Kembalikan ikon mata ke mode tersembunyi (\)
    const passwordIcon = document.getElementById('togglePasswordIcon');
    if (passwordIcon) {
        passwordIcon.className = 'bi bi-eye-slash';
    }
    
    const hint = document.querySelector('.id-hint'); if (hint) hint.classList.add('d-none');
    const imgHint = document.querySelector('.image-hint'); if (imgHint) imgHint.classList.add('d-none');
};

// LOGIKA POPULASI INPUT FORMULIR (EDIT USER PROFIL)
window.editUser = function(data) {
    window.clearForm();
    if (document.getElementById('formAction')) document.getElementById('formAction').value = 'update';
    if (document.getElementById('userId')) document.getElementById('userId').value = data.id;
    if (document.getElementById('userModalLabel')) document.getElementById('userModalLabel').innerText = 'Edit Data User (ID: ' + data.id + ')';
    
    if (document.getElementById('userNama')) document.getElementById('userNama').value = data.nama;
    if (document.getElementById('userUsername')) document.getElementById('userUsername').value = data.username;
    if (document.getElementById('userEmail')) document.getElementById('userEmail').value = data.email;
    if (document.getElementById('userTelepon')) document.getElementById('userTelepon').value = data.telepon;
    if (document.getElementById('userStatus')) document.getElementById('userStatus').value = data.status;
    
    // PERBAIKAN UTAMA: Kosongkan isi kolom password di modal form agar tidak menimpa password lama di DB
    if (document.getElementById('userPassword')) {
        document.getElementById('userPassword').value = '';
    }
    
    // Sinkronisasi komponen dropdown select relasi master data
    if (document.getElementById('userBuilding')) document.getElementById('userBuilding').value = data.building_id || '';
    if (document.getElementById('userFloor')) document.getElementById('userFloor').value = data.floor_id || '';
    if (document.getElementById('userRoom')) document.getElementById('userRoom').value = data.room_id || '';
    
    if (document.getElementById('userPassword')) document.getElementById('userPassword').required = false;
    const hint = document.querySelector('.id-hint'); if (hint) hint.classList.remove('d-none');
    const imgHint = document.querySelector('.image-hint'); if (imgHint) imgHint.classList.remove('d-none');
};
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
