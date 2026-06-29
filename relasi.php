<?php
require_once __DIR__ . '/auth.php';
require_login();

// 1. KONFIGURASI DATABASE
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Koneksi database gagal: " . $e->getMessage();
    die();
}

// 2. LOGIKA BACKEND: PROSES FORM CRUD (MENDUKUNG JAVASCRIPT FETCH)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {

            // --- MANAJEMEN BUILDINGS ---
        if ($action === 'add_building') {
            $nama = trim($_POST['nama'] ?? '');
            $alamat = trim($_POST['alamat'] ?? ''); // Tangkap data alamat
            if ($nama !== '') {
                // Tambahkan kolom alamat dan placeholder (?) di query Anda
                $stmt = $conn->prepare("INSERT INTO buildings (nama, alamat, status) VALUES (?, ?, 1)");
                $stmt->execute([$nama, $alamat]);
            }
        }
        if ($action === 'edit_building') {
            $id = (int)$_POST['id'];
            $nama = trim($_POST['nama'] ?? '');
            $alamat = trim($_POST['alamat'] ?? ''); // Tangkap data alamat
            if ($nama !== '') {
                // Tambahkan kolom alamat = ? di query UPDATE Anda
                $stmt = $conn->prepare("UPDATE buildings SET nama = ?, alamat = ? WHERE id = ?");
                $stmt->execute([$nama, $alamat, $id]);
            }
        }
        if ($action === 'delete_building') {
            $id = (int)$_POST['id'];
            $conn->prepare("DELETE FROM buildings WHERE id = ?")->execute([$id]);
        }

        // --- MANAJEMEN FLOORS ---
        if ($action === 'add_floor') {
            $nama = trim($_POST['nama'] ?? '');
            $building_id = (int)$_POST['building_id'];
            if ($nama !== '' && $building_id > 0) {
                $stmt = $conn->prepare("INSERT INTO floors (nama, building_id, status) VALUES (?, ?, 1)");
                $stmt->execute([$nama, $building_id]);
            }
        }
        if ($action === 'edit_floor') {
            $id = (int)$_POST['id'];
            $nama = trim($_POST['nama'] ?? '');
            $building_id = (int)$_POST['building_id'];
            if ($nama !== '' && $building_id > 0) {
                $stmt = $conn->prepare("UPDATE floors SET nama = ?, building_id = ? WHERE id = ?");
                $stmt->execute([$nama, $building_id, $id]);
            }
        }
        if ($action === 'delete_floor') {
            $id = (int)$_POST['id'];
            $conn->prepare("DELETE FROM floors WHERE id = ?")->execute([$id]);
        }

        // --- MANAJEMEN ROOMS ---
        if ($action === 'add_room') {
            $nama = trim($_POST['nama'] ?? '');
            $floor_id = (int)$_POST['floor_id'];
            $kode = trim($_POST['kode'] ?? '');      // Tangkap data kode
            $telepon = trim($_POST['telepon'] ?? ''); // Tangkap data telepon
            
            if ($nama !== '' && $floor_id > 0) {
                // Tambahkan kolom kode, telepon, dan placeholder (?, ?)
                $stmt = $conn->prepare("INSERT INTO rooms (nama, floor_id, kode, telepon, status) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$nama, $floor_id, $kode, $telepon]);
            }
        }
        if ($action === 'edit_room') {
            $id = (int)$_POST['id'];
            $nama = trim($_POST['nama'] ?? '');
            $floor_id = (int)$_POST['floor_id'];
            $kode = trim($_POST['kode'] ?? '');      // Tangkap data kode
            $telepon = trim($_POST['telepon'] ?? ''); // Tangkap data telepon
            
            if ($nama !== '' && $floor_id > 0) {
                // Tambahkan kolom kode = ?, telepon = ? di query UPDATE
                $stmt = $conn->prepare("UPDATE rooms SET nama = ?, floor_id = ?, kode = ?, telepon = ? WHERE id = ?");
                $stmt->execute([$nama, $floor_id, $kode, $telepon, $id]);
            }
        }

        if ($action === 'delete_room') {
            $id = (int)$_POST['id'];
            $conn->prepare("DELETE FROM rooms WHERE id = ?")->execute([$id]);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo "Database Error: " . $e->getMessage();
        die();
    }

    // Mengembalikan status sukses ke JavaScript tanpa paksaan redirect header lama
    http_response_code(200);
    exit;
}

// 3. AMBIL DATA MASTER UNTUK STATISTIK KARTU ATAS DAN DAFTAR DATA
$buildings = $conn->query("SELECT id, nama, alamat FROM buildings ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
$floors = $conn->query("SELECT f.id, f.nama, f.building_id, b.nama AS nama_bangunan FROM floors f LEFT JOIN buildings b ON f.building_id = b.id ORDER BY f.nama ASC")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $conn->query("SELECT r.id, r.nama, r.kode, r.telepon, r.floor_id, f.nama AS nama_lantai FROM rooms r LEFT JOIN floors f ON r.floor_id = f.id ORDER BY r.nama ASC")->fetchAll(PDO::FETCH_ASSOC);

// Hitung nilai agregat statistik atas untuk menyamakan gaya Dashboard Anda
$total_buildings = count($buildings);
$total_floors = count($floors);
$total_rooms = count($rooms);
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
        .sidebar { min-height: 100vh; background-color: #212529; color: white; }
        .sidebar a { color: #adb5bd; text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { color: white; background-color: #343a40; }
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

<!-- SIDEBAR MOBILE (OFFCANVAS) -->
<div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mobileSidebarLabel"><i class="bi bi-speedometer2"></i> ITAKMS</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <nav class="sidebar p-3 d-flex flex-column" style="min-height: calc(100vh - 56px);">
            <ul class="nav flex-column gap-2">
                <li class="nav-item">
                    <!-- STATUS ACTIVE UNTUK DASHBOARD -->
                    <a href="dashboard.php" class="nav-link p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="user.php" class="nav-link p-2 rounded"><i class="bi bi-person-lines-fill me-2"></i> User Profil</a>
                </li>
                <li class="nav-item">
                    <a href="roles.php" class="nav-link p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Roles</a>
                </li>
                <!-- MENU OPSI BARU: RELASI -->
                <li class="nav-item">
                    <a href="relasi.php" class="nav-link active p-2 rounded"><i class="bi bi-diagram-3 me-2"></i> Relasi</a>
                </li>
            </ul>
            <div class="mt-auto pt-3">
                <ul class="nav flex-column gap-2">
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link p-2 rounded" style="color:#dc3545 !important;">
                            <i class="bi bi-box-arrow-right me-2" style="color:#dc3545 !important;"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</div>

<!-- SIDEBAR DESKTOP -->
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-none d-md-flex flex-column sidebar p-3">
            <h4 class="text-center mb-4 text-warning"><i class="bi bi-speedometer2"></i> ITAKMS</h4>
            <ul class="nav flex-column gap-2">
                <li class="nav-item">
                    <!-- STATUS ACTIVE UNTUK DASHBOARD -->
                    <a href="dashboard.php" class="nav-link p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="user.php" class="nav-link p-2 rounded"><i class="bi bi-person-lines-fill me-2"></i> User Profil</a>
                </li>
                <li class="nav-item">
                    <a href="roles.php" class="nav-link p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Roles</a>
                </li>
                <!-- MENU OPSI BARU: RELASI -->
                <li class="nav-item">
                    <a href="relasi.php" class="nav-link active p-2 rounded"><i class="bi bi-diagram-3 me-2"></i> Relasi</a>
                </li>
            </ul>
            <div class="mt-auto pt-3 border-top border-secondary w-100">
                <ul class="nav flex-column gap-2">
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link p-2 rounded" style="color:#dc3545 !important;">
                            <i class="bi bi-box-arrow-right me-2" style="color:#dc3545 !important;"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- KONTEN UTAMA: MATRIKS MANAJEMEN UTAMA BUILDINGS, FLOORS, ROOMS -->
        <main class="col-md-9 col-12 px-2 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manajemen Bangunan & Ruang</h1>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary p-2">Sesi Admin</span>
                </div>
            </div>

            <!-- 3 KARTU STATISTIK ATAS -->
            <div class="row mb-4 gx-2">
                <div class="col-md-4">
                    <div class="card bg-primary text-white mb-3 shadow-sm border-0">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white-50">Total Gedung (Buildings)</h6>
                                <h2 class="card-text fw-bold"><?= $total_buildings ?></h2>
                            </div>
                            <i class="bi bi-building fs-1 text-white-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white mb-3 shadow-sm border-0">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white-50">Total Lantai (Floors)</h6>
                                <h2 class="card-text fw-bold"><?= $total_floors ?></h2>
                            </div>
                            <i class="bi bi-layers fs-1 text-white-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white mb-3 shadow-sm border-0">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white-50">Total Ruangan (Rooms)</h6>
                                <h2 class="card-text fw-bold"><?= $total_rooms ?></h2>
                            </div>
                            <i class="bi bi-door-open fs-1 text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRID MANAJEMEN 3 KOLOM UTAMA WITH CRUD -->
            <div class="row g-4">
                
                <!-- KOLOM 1: BUILDINGS -->
                <div class="col-12 col-lg-4">
                    <div class="card shadow-sm data-card bg-white rounded-3">
                        <div class="card-header bg-white fw-bold text-dark py-3 border-0 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-building text-primary me-2"></i>Buildings</span>
                            <button type="button" class="btn btn-sm btn-primary shadow-sm" onclick="bukaModalPaksa('modalBuilding')"><i class="bi bi-plus-lg"></i></button>
                        </div>
                        <ul class="list-group list-group-flush border-top">
                            <?php if (count($buildings) > 0): ?>
                                <?php foreach ($buildings as $b): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                                        <div>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($b['nama']) ?></div>
                                            <small class="text-muted d-block"><?= htmlspecialchars($b['alamat'] ?? 'Belum ada alamat') ?></small>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm text-warning p-0 border-0 bg-transparent" onclick="prosesEditBuilding('<?= $b['id'] ?>', '<?= htmlspecialchars($b['nama']) ?>', '<?= htmlspecialchars($b['alamat'] ?? '') ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm text-danger p-0 border-0 bg-transparent" onclick="prosesHapusCrud('delete_building', '<?= $b['id'] ?>', 'Hapus Gedung ini? Semua lantai dan ruangan di dalamnya ikut terpengaruh.')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center text-muted small py-3">Belum ada data gedung.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- KOLOM 2: FLOORS -->
                <div class="col-12 col-lg-4">
                    <div class="card shadow-sm data-card bg-white rounded-3">
                        <div class="card-header bg-white fw-bold text-dark py-3 border-0 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-layers text-success me-2"></i>Floors</span>
                            <button type="button" class="btn btn-sm btn-success shadow-sm" onclick="bukaModalPaksa('modalFloor')"><i class="bi bi-plus-lg"></i></button>
                        </div>
                        <ul class="list-group list-group-flush border-top">
                            <?php if (count($floors) > 0): ?>
                                <?php foreach ($floors as $f): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                                        <div>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($f['nama']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($f['nama_bangunan'] ?? 'Tanpa Gedung') ?></small>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm text-warning p-0 border-0 bg-transparent" onclick="prosesEditFloor('<?= $f['id'] ?>', '<?= htmlspecialchars($f['nama']) ?>', '<?= $f['building_id'] ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm text-danger p-0 border-0 bg-transparent" onclick="prosesHapusCrud('delete_floor', '<?= $f['id'] ?>', 'Hapus Lantai ini?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center text-muted small py-3">Belum ada data lantai.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- KOLOM 3: ROOMS -->
<div class="col-12 col-lg-4">
    <div class="card shadow-sm data-card bg-white rounded-3">
        <div class="card-header bg-white fw-bold text-dark py-3 border-0 d-flex justify-content-between align-items-center">
            <span><i class="bi bi-door-open text-danger me-2"></i>Rooms</span>
            <button type="button" class="btn btn-sm btn-danger shadow-sm" onclick="bukaModalPaksa('modalRoom')"><i class="bi bi-plus-lg"></i></button>
        </div>
        <ul class="list-group list-group-flush border-top">
            <?php if (count($rooms) > 0): ?>
                <?php foreach ($rooms as $r): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                        <div>
                            <!-- Menampilkan Nama Ruangan dan Kode Ruangan -->
                            <div class="fw-semibold text-dark">
                                <?= htmlspecialchars($r['nama']) ?> 
                                <?php if (!empty($r['kode'])): ?>
                                    <span class="badge bg-secondary ms-1 small"><?= htmlspecialchars($r['kode']) ?></span>
                                <?php endif; ?>
                            </div>
                            <!-- Menampilkan Nama Lantai dan Nomor Telepon jika ada -->
                            <small class="text-muted">
                                <?= htmlspecialchars($r['nama_lantai'] ?? 'Tanpa Lantai') ?>
                                <?php if (!empty($r['telepon'])): ?>
                                    <br><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($r['telepon']) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <div class="d-flex gap-2">
                            <!-- Menambahkan parameter data kode dan telepon ke fungsi JavaScript prosesEditRoom -->
                            <button type="button" class="btn btn-sm text-warning p-0 border-0 bg-transparent" onclick="prosesEditRoom('<?= $r['id'] ?>', '<?= htmlspecialchars($r['nama'], ENT_QUOTES) ?>', '<?= $r['floor_id'] ?>', '<?= htmlspecialchars($r['kode'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($r['telepon'] ?? '', ENT_QUOTES) ?>')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm text-danger p-0 border-0 bg-transparent" onclick="prosesHapusCrud('delete_room', '<?= $r['id'] ?>', 'Hapus Ruangan ini?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="list-group-item text-center text-muted small py-3">Belum ada data ruangan.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>

            </div>
        </main>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL POPUP INPUT DATA (TAMBAH DATA)       -->
<!-- ========================================== -->

<!-- Modal Building -->
<div class="modal fade" id="modalBuilding" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form onsubmit="prosesTambahCrud(event, 'add_building')">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Gedung</h5>
                    <button type="button" class="btn-close" onclick="tutupModalPaksa('modalBuilding')"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Gedung</label>
                        <input type="text" name="nama" class="form-control" required placeholder="Contoh: Gedung A">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Gedung</label>
                        <textarea name="alamat" class="form-control" rows="3" placeholder="Contoh: Jl. Ahmad Yani No. 12" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalPaksa('modalBuilding')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Floor -->
<div class="modal fade" id="modalFloor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form onsubmit="prosesTambahCrud(event, 'add_floor')">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Lantai</h5>
                    <button type="button" class="btn-close" onclick="tutupModalPaksa('modalFloor')"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Gedung</label>
                        <select name="building_id" class="form-select" required>
                            <option value="">-- Pilih Gedung --</option>
                            <?php foreach ($buildings as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lantai</label>
                        <input type="text" name="nama" class="form-control" required placeholder="Contoh: Lantai 1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalPaksa('modalFloor')">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Room -->
<div class="modal fade" id="modalRoom" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form onsubmit="prosesTambahCrud(event, 'add_room')">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Ruangan</h5>
                    <button type="button" class="btn-close" onclick="tutupModalPaksa('modalRoom')"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Lantai</label>
                        <select name="floor_id" class="form-select" required>
                            <option value="">-- Pilih Lantai --</option>
                            <?php foreach ($floors as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nama']) ?> (<?= htmlspecialchars($f['nama_bangunan'] ?? 'Tanpa Gedung') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Ruangan</label>
                        <input type="text" name="nama" class="form-control" required placeholder="Contoh: Ruang Rapat 401">
                    </div>
                    <!-- INPUT BARU: KODE RUANGAN -->
                    <div class="mb-3">
                        <label class="form-label">Kode Ruangan</label>
                        <input type="text" name="kode" class="form-control" placeholder="Contoh: R01">
                    </div>
                    <!-- INPUT BARU: TELEPON RUANGAN -->
                    <div class="mb-3">
                        <label class="form-label">Telepon Ruangan</label>
                        <input type="text" name="telepon" class="form-control" placeholder="Contoh: 021-xxxx">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalPaksa('modalRoom')">Batal</button>
                    <!-- Mengubah btn-danger menjadi btn-primary agar warna tombol simpan seragam -->
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL POPUP EDIT DATA                      -->
<!-- ========================================== -->

<!-- Modal Edit Building -->
<div class="modal fade" id="modalEditBuilding" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form onsubmit="simpanEditCrud(event, 'edit_building')">
                <input type="hidden" name="id" id="edit_b_id">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Data Gedung</h5>
                    <button type="button" class="btn-close" onclick="tutupModalPaksa('modalEditBuilding')"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Gedung</label>
                        <input type="text" name="nama" id="edit_b_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Gedung</label>
                        <textarea name="alamat" id="edit_b_alamat" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalPaksa('modalEditBuilding')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Floor -->
<div class="modal fade" id="modalEditFloor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form onsubmit="simpanEditCrud(event, 'edit_floor')">
                <input type="hidden" name="id" id="edit_f_id">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Lantai</h5>
                    <button type="button" class="btn-close" onclick="tutupModalPaksa('modalEditFloor')"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Gedung</label>
                        <select name="building_id" id="edit_f_building_id" class="form-select" required>
                            <?php foreach ($buildings as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lantai</label>
                        <input type="text" name="nama" id="edit_f_nama" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalPaksa('modalEditFloor')">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Room -->
<div class="modal fade" id="modalEditRoom" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form onsubmit="simpanEditCrud(event, 'edit_room')">
                <input type="hidden" name="id" id="edit_r_id">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Ruangan</h5>
                    <button type="button" class="btn-close" onclick="tutupModalPaksa('modalEditRoom')"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Lantai</label>
                        <select name="floor_id" id="edit_r_floor_id" class="form-select" required>
                            <?php foreach ($floors as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Ruangan</label>
                        <input type="text" name="nama" id="edit_r_nama" class="form-control" required>
                    </div>
                    <!-- INPUT BARU: KODE RUANGAN -->
                    <div class="mb-3">
                        <label class="form-label">Kode Ruangan</label>
                        <input type="text" name="kode" id="edit_r_kode" class="form-control" placeholder="Contoh: R01">
                    </div>
                    <!-- INPUT BARU: TELEPON RUANGAN -->
                    <div class="mb-3">
                        <label class="form-label">Telepon Ruangan</label>
                        <input type="text" name="telepon" id="edit_r_telepon" class="form-control" placeholder="Contoh: 021-xxxx">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalPaksa('modalEditRoom')">Batal</button>
                    <!-- Mengubah btn-danger menjadi btn-primary agar selaras dengan tombol simpan lainnya -->
                    <button type="submit" class="btn btn-primary">Simpan</button> 
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- LOGIKA JAVASCRIPT UTAMA AJAX CRUD          -->
<!-- ========================================== -->
<script>
// 1. Fungsi Membuka Modal secara Paksa (Anti Bentrok Library)
function bukaModalPaksa(idModal) {
    const modalTarget = document.getElementById(idModal);
    if (modalTarget) {
        modalTarget.classList.add('show');
        modalTarget.style.display = 'block';
        modalTarget.removeAttribute('aria-hidden');
        modalTarget.setAttribute('aria-modal', 'true');
        modalTarget.setAttribute('role', 'dialog');
        
        // Membuat efek backdrop hitam transparan di belakang modal
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.id = 'backdrop-' + idModal;
        document.body.appendChild(backdrop);
        document.body.classList.add('modal-open');
    }
}

// 2. Fungsi Menutup Modal secara Paksa
function tutupModalPaksa(idModal) {
    const modalTarget = document.getElementById(idModal);
    if (modalTarget) {
        modalTarget.classList.remove('show');
        modalTarget.style.display = 'none';
        modalTarget.setAttribute('aria-hidden', 'true');
        modalTarget.removeAttribute('aria-modal');
        modalTarget.removeAttribute('role');
        
        const backdrop = document.getElementById('backdrop-' + idModal);
        if (backdrop) backdrop.remove();
        document.body.classList.remove('modal-open');
    }
}

// Event otomatis mendeteksi klik tombol X (close) bawaan modal
document.addEventListener("click", function(e) {
    if (e.target.classList.contains('btn-close') || e.target.getAttribute('data-bs-dismiss') === 'modal') {
        const modalTerbuka = e.target.closest('.modal');
        if (modalTerbuka) {
            tutupModalPaksa(modalTerbuka.id);
        }
    }
});

// 3. Fungsi Tambah Data Berbagai Komponen (Create)
function prosesTambahCrud(event, aksi) {
    event.preventDefault(); 
    let targetForm = event.target;
    let payload = new FormData(targetForm);
    payload.append('action', aksi); 

    fetch('relasi.php', {
        method: 'POST',
        body: payload
    })
    .then(() => {
        location.reload(); 
    })
    .catch(err => {
        alert('Gagal menyimpan data baru.');
        console.error(err);
    });
}

// 4. Fungsi Hapus Data Berbagai Komponen (Delete)
function prosesHapusCrud(aksi, idTarget, teksKonfirmasi) {
    if (!confirm(teksKonfirmasi)) return;
    let payload = new FormData();
    payload.append('action', aksi);
    payload.append('id', idTarget);

    fetch('relasi.php', {
        method: 'POST',
        body: payload
    })
    .then(() => {
        location.reload();
    })
    .catch(err => {
        alert('Gagal terhubung dengan server.');
    });
}

// 5. Fungsi Pemicu Pengisian Data Lama ke Input Form Modal Edit
function prosesEditBuilding(id, namaLama, alamatLama) {
    document.getElementById('edit_b_id').value = id;
    document.getElementById('edit_b_nama').value = namaLama;
    document.getElementById('edit_b_alamat').value = alamatLama; 
    bukaModalPaksa('modalEditBuilding');
}

function prosesEditFloor(id, namaLama, buildingIdLama) {
    document.getElementById('edit_f_id').value = id;
    document.getElementById('edit_f_nama').value = namaLama;
    document.getElementById('edit_f_building_id').value = buildingIdLama;
    bukaModalPaksa('modalEditFloor');
}

function prosesEditRoom(id, namaLama, floorIdLama, kodeLama, teleponLama) {
    document.getElementById('edit_r_id').value = id;
    document.getElementById('edit_r_nama').value = namaLama;
    document.getElementById('edit_r_floor_id').value = floorIdLama;
    document.getElementById('edit_r_kode').value = kodeLama;
    document.getElementById('edit_r_telepon').value = teleponLama;
    bukaModalPaksa('modalEditRoom');
}

// 6. Fungsi Eksekusi Pengiriman Data Modifikasi Baru ke Server (Update)
function simpanEditCrud(event, aksi) {
    event.preventDefault();
    let payload = new FormData(event.target);
    payload.append('action', aksi);

    fetch('relasi.php', {
        method: 'POST',
        body: payload
    })
    .then(() => {
        location.reload();
    })
    .catch(err => {
        alert('Gagal menyimpan perubahan data.');
    });
}
</script>

<!-- Bootstrap Bundle JS Resmi -->
<script src="https://jsdelivr.net" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
