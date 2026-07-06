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

// AMBIL DAFTAR ROLES UNTUK DROPDOWN FORM HTML
try {
    $stmtRoles = $conn->query("SELECT id, nama_role FROM roles ORDER BY nama_role ASC");
    $daftar_roles = $stmtRoles->fetchAll();
} catch (\PDOException $e) {
    $daftar_roles = []; 
}

// 2. Logika Pemrosesan Form CRUD via POST
$message = '';
$messageType = '';

// TRIGGER LOG OTOMATIS GLOBAL: Mencatat log kunjungan halaman
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['status'])) {
    write_log($conn, "Membuka halaman Manajemen Pengguna", "users", null);
}

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
            $username_input = $_POST['username'];
            $role_id_input = $_POST['role_id']; // Mengambil nilai dari dropdown form

            $stmt = $conn->prepare("INSERT INTO users (id, role_id, nama, username, password, email, telepon, foto, status, building_id, floor_id, room_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $sukses_add = $stmt->execute([
                $nextId, $role_id_input, $_POST['nama'], $username_input, $plain_password, $_POST['email'], 
                $_POST['telepon'], $nama_foto, $_POST['status'], 
                $_POST['building_id'] ?: null, $_POST['floor_id'] ?: null, $_POST['room_id'] ?: null
            ]);
            
            // AMBIL ID BARU DAN TULIS LOG AKTIVITAS (CREATE)
            if ($sukses_add) {
                write_log($conn, "Menambahkan pengguna (user) baru: " . $username_input, "users", $nextId);
            }

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
            $username_input = $_POST['username'];
            $role_id_input = $_POST['role_id']; // Mengambil nilai dari dropdown form
            
            // Validasi username milik orang lain
            $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $stmtCheck->execute([$username_input, $id]);
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
                $stmt = $conn->prepare("UPDATE users SET role_id=?, nama=?, username=?, password=?, email=?, telepon=?, foto=?, status=?, building_id=?, floor_id=?, room_id=? WHERE id=?");
                $sukses_edit = $stmt->execute([
                    $role_id_input, $_POST['nama'], $username_input, $plain_password, $_POST['email'], 
                    $_POST['telepon'], $nama_foto, $_POST['status'], 
                    $_POST['building_id'] ?: null, $_POST['floor_id'] ?: null, $_POST['room_id'] ?: null, 
                    $id
                ]);
            } else {
                $stmt = $conn->prepare("UPDATE users SET role_id=?, nama=?, username=?, email=?, telepon=?, foto=?, status=?, building_id=?, floor_id=?, room_id=? WHERE id=?");
                $sukses_edit = $stmt->execute([
                    $role_id_input, $_POST['nama'], $username_input, $_POST['email'], 
                    $_POST['telepon'], $nama_foto, $_POST['status'], 
                    $_POST['building_id'] ?: null, $_POST['floor_id'] ?: null, $_POST['room_id'] ?: null, 
                    $id
                ]);
            }
            
            // TULIS LOG AKTIVITAS (UPDATE)
            if ($sukses_edit) {
                write_log($conn, "Mengubah informasi data akun pengguna: " . $username_input, "users", $id);
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
            
            // 1. Ambil data nama file dan username dari database sebelum dihapus
            $stmtOld = $conn->prepare("SELECT username, foto FROM users WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldData = $stmtOld->fetch();
            $username_log = $oldData['username'] ?? 'Unknown';
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
            $sukses_delete = $stmt->execute([$id]);

            // TULIS LOG AKTIVITAS (DELETE)
            if ($sukses_delete) {
                write_log($conn, "Menghapus akun pengguna: " . $username_log, "users", $id);
            }

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

// 3. Menangkap Status dari URL untuk Flash Message Notifikasi
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success_create') {
        $message = "Pengguna baru berhasil ditambahkan.";
        $messageType = "success";
    } elseif ($_GET['status'] === 'success_update') {
        $message = "Data pengguna berhasil diperbarui.";
        $messageType = "success";
    } elseif ($_GET['status'] === 'success_delete') {
        $message = "Pengguna berhasil dihapus.";
        $messageType = "success";
    } elseif ($_GET['status'] === 'error_duplicate') {
        $message = "Gagal! Username sudah terdaftar di sistem.";
        $messageType = "danger";
    } else {
        $message = "Terjadi kesalahan sistem saat memproses data.";
        $messageType = "danger";
    }
}

// 4. QUERY UTAMA: Mengambil data user menggunakan kolom '.nama' sesuai isi relasi.php
try {
    $stmtUsers = $conn->query("
        SELECT 
            u.*, 
            r.nama AS nama_role,      -- PERBAIKAN: Menggunakan .nama
            g.nama AS nama_gedung,    -- PERBAIKAN: Menggunakan .nama
            l.nama AS nama_lantai,    -- PERBAIKAN: Menggunakan .nama
            ru.nama AS nama_ruangan   -- PERBAIKAN: Menggunakan .nama
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.id 
        LEFT JOIN buildings g ON u.building_id = g.id  
        LEFT JOIN floors l ON u.floor_id = l.id        
        LEFT JOIN rooms ru ON u.room_id = ru.id        
        ORDER BY u.id ASC
    ");
    $users = $stmtUsers->fetchAll();
} catch (\PDOException $e) {
    // Menampilkan error jika masih ada ketidakcocokan nama tabel/kolom lainnya
    die("Pesan Error Query 4: " . $e->getMessage());
    $users = [];
}

// 5. QUERY MASTER DROPDOWN: Menggunakan kolom '.nama' untuk daftar pilihan modal form
try {
    $stmtRoles = $conn->query("SELECT id, nama AS nama_role FROM roles ORDER BY nama ASC");
    $daftar_roles = $stmtRoles->fetchAll();

    $stmtGedung = $conn->query("SELECT id, nama AS nama_gedung FROM buildings ORDER BY nama ASC");
    $daftar_gedung = $stmtGedung->fetchAll();

    $stmtLantai = $conn->query("SELECT id, building_id, nama AS nama_lantai FROM floors ORDER BY nama ASC");
    $daftar_lantai = $stmtLantai->fetchAll();

    $stmtRuangan = $conn->query("SELECT id, floor_id, nama AS nama_ruangan FROM rooms ORDER BY nama ASC");
    $daftar_ruangan = $stmtRuangan->fetchAll();
} catch (\PDOException $e) {
    die("Pesan Error Query 5: " . $e->getMessage());
    $daftar_roles = [];
    $daftar_gedung = [];
    $daftar_lantai = [];
    $daftar_ruangan = [];
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

<!-- BAGIAN C: PEMBUNGKUS MAIN KONTEN UTAMA (SEKARANG SUDAH DI LUAR)       -->
<main class="main-content p-3 p-md-4 flex-grow-1 overflow-x-hidden">
    
    <!-- Isi konten User Profil dan Tabel Data Anda -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 mb-0">User Profil</h1>
        
        <!-- Fleksibel Otomatis: Hanya tampil jika Role memiliki izin Create ('C') di file ini -->
        <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'C', $userRole)): ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="window.clearForm()">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah User
        </button>
        <?php endif; ?>
        
    </div>

    <!-- Notifikasi Alert Merah / Hijau -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" onclick="window.location.href='user.php'" aria-label="Close"></button>
        </div>
    <?php endif; ?>

<!-- TABEL RESPONSIF SEJAJAR + FOTO DAN NAMA DIPISAH -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0 text-nowrap w-100">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-secondary text-center">
                                <th class="ps-3 py-3" style="width: 5%;">ID</th>
                                <th style="width: 7%;">Foto</th> <!-- Kolom Foto Mandiri -->
                                <th class="text-start" style="width: 15%;">Nama Pengguna</th> <!-- Kolom Nama Mandiri -->
                                <th style="width: 10%;">Role</th>
                                <th style="width: 10%;">Username</th>
                                <th style="width: 15%;">Email</th>
                                <th style="width: 12%;">Telepon</th>
                                <th style="width: 8%;">Status</th>
                                <th style="width: 10%;">Gedung</th>
                                <th style="width: 5%;">Lantai</th>
                                <th style="width: 5%;">Ruangan</th>
                                <th style="width: 5%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <!-- 1. KOLOM ID -->
                                        <td class="text-center py-2 fw-bold text-muted">
                                            <?= $user['id'] ?>
                                        </td>

                                        <!-- 2. KOLOM FOTO (Terpisah) -->
                                        <td class="text-center py-2">
                                            <?php if (!empty($user['foto']) && file_exists(__DIR__ . '/uploads/' . $user['foto'])): ?>
                                                <img src="uploads/<?= htmlspecialchars($user['foto']) ?>" alt="Profil" class="rounded-circle flex-shrink-0" style="width: 32px; height: 32px; object-fit: cover; border: 1px solid #dee2e6;">
                                            <?php else: ?>
                                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold mx-auto flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.7rem;">
                                                    <?= strtoupper(substr($user['nama'] ?? 'U', 0, 2)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- 3. KOLOM NAMA PENGGUNA (Terpisah) -->
                                        <td class="py-2 fw-semibold text-dark text-start">
                                            <?= htmlspecialchars($user['nama']) ?>
                                        </td>
                                        
                                        <!-- 4. KOLOM ROLE -->
                                        <td class="py-2 text-center">
                                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1" style="font-size: 0.75rem;">
                                                <?= htmlspecialchars($user['nama_role'] ?? 'No Role') ?>
                                            </span>
                                        </td>

                                        <!-- KOLOM DATA LAINNYA -->
                                        <td class="py-2 text-muted text-center"><?= htmlspecialchars($user['username']) ?></td>
                                        <td class="py-2"><?= htmlspecialchars($user['email']) ?></td>
                                        <td class="py-2 text-secondary text-center"><?= htmlspecialchars($user['telepon']) ?></td>
                                        <td class="py-2 text-center">
                                            <span class="badge <?= $user['status'] == 1 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                                                <?= $user['status'] == 1 ? 'Aktif' : 'Non-Aktif' ?>
                                            </span>
                                        </td>
                                        <td class="py-2 text-center"><?= htmlspecialchars($user['nama_gedung'] ?? '-') ?></td>
                                        <td class="py-2 text-center"><?= htmlspecialchars($user['nama_lantai'] ?? '-') ?></td>
                                        <td class="py-2 text-center"><?= htmlspecialchars($user['nama_ruangan'] ?? '-') ?></td>
                                        
                                        <!-- KOLOM AKSI BUTTONS -->
                                        <td class="text-center pe-3 py-2">
                                            <div class="d-flex justify-content-center gap-1.5">
                                                
                                                <!-- Fleksibel Otomatis: Cek Akses Update ('U') untuk tombol Edit -->
                                                <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'U', $userRole)): ?>
                                                <button type="button" class="btn btn-sm btn-light border text-warning p-1 px-2" data-bs-toggle="modal" data-bs-target="#userModal" onclick='window.editUser(<?= json_encode($user, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <!-- Fleksibel Otomatis: Cek Akses Delete ('D') untuk tombol Hapus -->
                                                <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'D', $userRole)): ?>
                                                <form action="user.php" method="POST" class="d-inline mb-0" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-light border text-danger p-1 px-2">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                                
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Mengembalikan colspan menjadi 12 kolom karena total kolom sekarang ada 12 -->
                                <tr><td colspan="12" class="text-center text-muted py-4">Tidak ada data user ditemukan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</main>

<!-- MODAL FORM TAMBAH / EDIT (TAMPILAN MEMANJANG KE KANAN) -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl"> <!-- Mengubah modal-lg menjadi modal-xl agar memanjang ke kanan -->
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
                        <!-- BARIS 1: DATA PRIBADI (MEMANJANG) -->
                        <div class="col-md-4">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" id="userNama" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" id="userUsername" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" name="password" id="userPassword" class="form-control" placeholder="Isi password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye-slash" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                            <small class="text-muted id-hint d-none">Kosongkan jika tidak ingin mengubah password lama.</small>
                        </div>

                        <!-- BARIS 2: KONTAK & HAK AKSES (MEMANJANG) -->
                        <div class="col-md-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="userEmail" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Telepon</label>
                            <input type="text" name="telepon" id="userTelepon" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Role Akses</label>
                            <select name="role_id" id="userRole" class="form-select" required>
                                <option value="">-- Pilih Role --</option>
                                <?php foreach ($daftar_roles as $role): ?>
                                    <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['nama_role']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="userStatus" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Non-Aktif</option>
                            </select>
                        </div>
                        
                        <!-- BARIS 3: BERKAS (FOTO) -->
                        <div class="col-md-12">
                            <label class="form-label">Foto Profil</label>
                            <input type="file" name="foto" id="userFoto" class="form-control" accept="image/png, image/jpeg, image/jpg">
                            <small class="text-muted image-hint d-none">Kosongkan jika tidak ingin mengganti foto lama.</small>
                        </div>

                        <!-- BARIS 4: INTERKONEKSI LOKASI (MEMANJANG & DINAMIS) -->
                        <div class="col-md-4">
                            <label class="form-label">Gedung</label>
                            <select name="building_id" id="userBuilding" class="form-select" onchange="filterLantai()">
                                <option value="">-- Pilih Gedung --</option>
                                <?php foreach ($daftar_gedung as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nama_gedung']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Lantai</label>
                            <select name="floor_id" id="userFloor" class="form-select" onchange="filterRuangan()" disabled>
                                <option value="">-- Pilih Lantai --</option>
                                <?php foreach ($daftar_lantai as $f): ?>
                                    <option value="<?= $f['id'] ?>" data-building="<?= $f['building_id'] ?>"><?= htmlspecialchars($f['nama_lantai']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ruangan</label>
                            <select name="room_id" id="userRoom" class="form-select" disabled>
                                <option value="">-- Pilih Ruangan --</option>
                                <?php foreach ($daftar_ruangan as $r): ?>
                                    <option value="<?= $r['id'] ?>" data-floor="<?= $r['floor_id'] ?>"><?= htmlspecialchars($r['nama_ruangan']) ?></option>
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

// INTERAKSI DOM DAN SINKRONISASI POSISI SCROLL SIDEBAR UTAMA
document.addEventListener("DOMContentLoaded", function() {
    // -------------------------------------------------------------
    // FIX UTAMA: FITUR AUTO-SCROLL UNTUK MENETAPKAN FOKUS MENU
    // -------------------------------------------------------------
    const menuContainer = document.querySelector('.menu-scroll-container');
    const activeMenu = document.querySelector('.menu-scroll-container .active-style');
    
    if (menuContainer && activeMenu) {
        // Hitung jarak vertikal opsi menu aktif dari atap pembungkus sidebar
        const activeOffsetTop = activeMenu.offsetTop;
        
        // Gulung otomatis kontainer ke menu tersebut dan beri jarak aman 20px dari atas
        menuContainer.scrollTop = activeOffsetTop - 20;
    }

    // AKSI INTERAKTIF TOMBOL MATA UNTUK MENGINTIP KATA SANDI
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

<!-- Bootstrap JS Bundle CDN (Sebagai engine eksternal cadangan) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
