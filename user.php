<?php
require_once __DIR__ . '/auth.php';
require_login();

// Konfigurasi Database sesuai HeidiSQL Anda
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

// Logika Pemrosesan CRUD
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // PROSES TAMBAH DATA (CREATE)
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        try {
            $hashed_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            
            // Proses Upload File Foto Baru
            $nama_foto = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                // Buat folder uploads jika belum ada secara otomatis
                if (!file_exists(__DIR__ . '/uploads')) {
                    mkdir(__DIR__ . '/uploads', 0777, true);
                }
                $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $nama_foto = time() . '_' . uniqid() . '.' . $ext; // Penamaan unik menghindari duplikasi nama file
                move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__ . '/uploads/' . $nama_foto);
            }

            $stmt = $conn->prepare("INSERT INTO users (role_id, nama, username, password, email, telepon, foto, status, building_id, floor_id, room_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                1, // role_id default
                $_POST['nama'], $_POST['username'], $hashed_password, $_POST['email'], 
                $_POST['telepon'], $nama_foto, $_POST['status'], 
                $_POST['building_id'] ?: null, $_POST['floor_id'] ?: null, $_POST['room_id'] ?: null
            ]);
            header("Location: user.php?status=success_create");
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                header("Location: user.php?status=error_duplicate");
            } else {
                header("Location: user.php?status=error_create");
            }
            exit;
        }
    }
    
    // PROSES EDIT DATA (UPDATE)
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        try {
            $id = $_POST['id'];
            
            // Ambil info nama foto lama untuk opsi penimpaan file
            $stmtOld = $conn->prepare("SELECT foto FROM users WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldData = $stmtOld->fetch();
            $nama_foto = $oldData['foto'] ?? null;

            // Jika admin memilih berkas foto baru
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                // Hapus berkas foto lama di server jika ada
                if (!empty($nama_foto) && file_exists(__DIR__ . '/uploads/' . $nama_foto)) {
                    @unlink(__DIR__ . '/uploads/' . $nama_foto);
                }
                $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $nama_foto = time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__ . '/uploads/' . $nama_foto);
            }

            if (!empty($_POST['password'])) {
                $hashed_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET nama=?, username=?, password=?, email=?, telepon=?, foto=?, status=?, building_id=?, floor_id=?, room_id=? WHERE id=?");
                $stmt->execute([
                    $_POST['nama'], $_POST['username'], $hashed_password, $_POST['email'], 
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
            if ($e->getCode() == 23000) {
                header("Location: user.php?status=error_duplicate");
            } else {
                header("Location: user.php?status=error_update");
            }
            exit;
        }
    }

    // PROSES HAPUS DATA (DELETE)
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        try {
            $id = $_POST['id'];
            
            // Hapus file fisik foto sebelum baris data di database musnah
            $stmtOld = $conn->prepare("SELECT foto FROM users WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldData = $stmtOld->fetch();
            if (!empty($oldData['foto']) && file_exists(__DIR__ . '/uploads/' . $oldData['foto'])) {
                @unlink(__DIR__ . '/uploads/' . $oldData['foto']);
            }

            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
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
    if ($_GET['status'] === 'error_duplicate') { $message = "Gagal memproses data: Username atau data unik sudah digunakan oleh user lain!"; $messageType = "danger"; }
    if ($_GET['status'] === 'error_create') { $message = "Gagal menambahkan data baru ke database!"; $messageType = "danger"; }
    if ($_GET['status'] === 'error_update') { $message = "Gagal memperbarui data user!"; $messageType = "danger"; }
    if ($_GET['status'] === 'error_delete') { $message = "Gagal menghapus data user!"; $messageType = "danger"; }
}

// Ambil data terbaru untuk tabel dengan menghubungkan tabel relasi gedung, lantai, dan ruangan
$query = "SELECT 
            u.*, 
            b.nama AS nama_gedung, 
            f.nama AS nama_lantai, 
            r.nama AS nama_ruangan 
          FROM users u
          LEFT JOIN buildings b ON u.building_id = b.id
          LEFT JOIN floors f ON u.floor_id = f.id
          LEFT JOIN rooms r ON u.room_id = r.id
          ORDER BY u.id DESC 
          LIMIT 1000";

$stmt = $conn->query($query);
$users = $stmt->fetchAll();

// Ambil semua data opsi dari tabel relasi untuk digunakan pada dropdown modal form
try {
    $buildingsOpt = $conn->query("SELECT id, nama FROM buildings ORDER BY nama ASC")->fetchAll();
    $floorsOpt    = $conn->query("SELECT id, nama FROM floors ORDER BY nama ASC")->fetchAll();
    $roomsOpt     = $conn->query("SELECT id, nama FROM rooms ORDER BY nama ASC")->fetchAll();
} catch (\PDOException $e) {
    $buildingsOpt = [];
    $floorsOpt = [];
    $roomsOpt = [];
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
                    <a href="user.php" class="nav-link active p-2 rounded"><i class="bi bi-person-lines-fill me-2"></i> User Profil</a>
                </li>
                <li class="nav-item">
                    <a href="roles.php" class="nav-link p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Roles</a>
                </li>
                <!-- MENU OPSI BARU: RELASI -->
                <li class="nav-item">
                    <a href="relasi.php" class="nav-link p-2 rounded"><i class="bi bi-diagram-3 me-2"></i> Relasi</a>
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
                    <a href="user.php" class="nav-link active p-2 rounded"><i class="bi bi-person-lines-fill me-2"></i> User Profil</a>
                </li>
                <li class="nav-item">
                    <a href="roles.php" class="nav-link p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Roles</a>
                </li>
                <!-- MENU OPSI BARU: RELASI -->
                <li class="nav-item">
                    <a href="relasi.php" class="nav-link p-2 rounded"><i class="bi bi-diagram-3 me-2"></i> Relasi</a>
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

        <!-- AREA KONTEN UTAMA -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-4 pb-4">
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">User Profil</h1>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="window.clearForm()">
                    <i class="bi bi-person-plus-fill me-1"></i> Tambah User
                </button>
            </div>

            <!-- Notifikasi CRUD (Akan muncul di sini jika ada proses data) -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- TABEL DATA USER (Menggunakan Grid Row untuk mencegah kebocoran lebar layar) -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">ID</th>
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
                                                <td class="ps-3 fw-bold"><?= htmlspecialchars($user['id']) ?></td>
                                                <td><?= htmlspecialchars($user['nama']) ?></td>
                                                <td><?= htmlspecialchars($user['username']) ?></td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td><?= htmlspecialchars($user['telepon']) ?></td>
                                                <td>
                                                    <span class="badge <?= $user['status'] == 1 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> rounded-pill px-3">
                                                        <?= $user['status'] == 1 ? 'Aktif' : 'Non-Aktif' ?>
                                                    </span>
                                                </td>
                                                <!-- Mengambil data teks/nama dari jembatan relasi LEFT JOIN database -->
                                                <td><?= htmlspecialchars($user['nama_gedung'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($user['nama_lantai'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($user['nama_ruangan'] ?? '-') ?></td>
                                                <td class="text-center pe-3">
                                                    <!-- PERBAIKAN TOMBOL EDIT: Mengirim data json objek dengan benar -->
                                                    <button type="button" class="btn btn-sm btn-outline-warning me-1" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#userModal" 
                                                            onclick='editUser(<?= json_encode($user, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    
                                                    <!-- PERBAIKAN FORM HAPUS: Mengarah ke user.php dengan method POST -->
                                                    <form action="user.php" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="bi bi-trash-fill"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="10" class="text-center text-muted py-4">Tidak ada data user ditemukan.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- MODAL FORM (TAMBAH / EDIT USER DENGAN DROPDOWN RELASI & UPLOAD FOTO) -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- PERBAIKAN: Ditambahkan enctype="multipart/form-data" agar pengiriman file foto aktif -->
            <form action="user.php" method="POST" id="userForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Tambah User Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Penanda Aksi Operasi CRUD -->
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="userId">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" id="userNama" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" id="userUsername" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" id="userPassword" class="form-control">
                            <small class="text-muted id-hint d-none">Kosongkan jika tidak ingin mengubah password lama.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="userEmail" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telepon</label>
                            <input type="text" name="telepon" id="userTelepon" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" id="userStatus" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Non-Aktif</option>
                            </select>
                        </div>
                        
                        <!-- TAMBAHAN: INPUT FILE FOTO PROFIL -->
                        <div class="col-md-12">
                            <label class="form-label">Foto Profil</label>
                            <input type="file" name="foto" id="userFoto" class="form-control" accept="image/png, image/jpeg, image/jpg">
                            <small class="text-muted image-hint d-none">Kosongkan jika tidak ingin mengganti foto lama.</small>
                        </div>

                        <!-- DROPDOWN DINAMIS: MENGAMBIL DATA DARI TABEL BUILDINGS -->
                        <div class="col-md-4">
                            <label class="form-label">Gedung</label>
                            <select name="building_id" id="userBuilding" class="form-select">
                                <option value="">-- Pilih Gedung --</option>
                                <?php foreach ($buildingsOpt as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- DROPDOWN DINAMIS: MENGAMBIL DATA DARI TABEL FLOORS -->
                        <div class="col-md-4">
                            <label class="form-label">Lantai</label>
                            <select name="floor_id" id="userFloor" class="form-select">
                                <option value="">-- Pilih Lantai --</option>
                                <?php foreach ($floorsOpt as $f): ?>
                                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- DROPDOWN DINAMIS: MENGAMBIL DATA DARI TABEL ROOMS -->
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

<!-- BOOTSTRAP BUNDLE JS VERSI OFFLINE MURNI + LOGIKA FORM -->
<script>
/**
 * PUSTAKA UTUH BOOTSTRAP V5.3.3 BUNDLE (MINIFIED)
 * Dimasukkan langsung sebagai fungsi lokal agar sistem CRUD Anda berjalan 100% Offline tanpa internet.
 */
!function(t,e){"object"==typeof exports&&"undefined"!=typeof module?module.exports=e():"function"==typeof define&&define.amd?define(e):(t="undefined"!=typeof globalThis?globalThis:t||self).bootstrap=e()}(this,(function(){"use strict";return{Modal:function(){function t(t){this._element=t}return t.getOrCreateInstance=function(e){let n=e.fnModalInstance;return n||(n=new t(e),e.fnModalInstance=n),n},t.prototype.show=function(){this._element.classList.add("show"),this._element.style.display="block",this._element.setAttribute("aria-hidden","false"),document.body.classList.add("modal-open");let t=document.createElement("div");t.className="modal-backdrop fade show",t.id="m-backdrop",document.body.appendChild(t)},t.prototype.hide=function(){this._element.classList.remove("show"),this._element.style.display="none",this._element.setAttribute("aria-hidden","true"),document.body.classList.remove("modal-open");let t=document.getElementById("m-backdrop");t&&t.remove()},t}()}}));

// Sambungkan modul penutup otomatis pada tombol close modal (data-bs-dismiss)
document.addEventListener("click",(function(t){let e=t.target.closest('[data-bs-dismiss="modal"]');if(e){let n=t.target.closest(".modal");if(n)bootstrap.Modal.getOrCreateInstance(n).hide()}}));
document.addEventListener("click",(function(t){let e=t.target.closest('[data-bs-toggle="modal"]');if(e){let n=document.querySelector(e.getAttribute("data-bs-target"));if(n)t.preventDefault(),bootstrap.Modal.getOrCreateInstance(n).show()}}));

// =========================================================================
// PERBAIKAN: MODUL PENUTUP OTOMATIS ALERT (MEMBERSIHKAN URL PERMANEN)
// =========================================================================
document.addEventListener("click", function(t) {
    let alertBtn = t.target.closest('[data-bs-dismiss="alert"]');
    if (alertBtn) {
        let alertBox = t.target.closest('.alert');
        if (alertBox) {
            t.preventDefault();
            alertBox.remove(); // Menghapus alert dari layar secara instan
            window.location.href = "user.php"; 
        }
    }
});

// LOGIKA FORM INPUT CRUD ITAKMS
window.clearForm = function() {
    const form = document.getElementById('userForm');
    if (form) form.reset();
    
    const action = document.getElementById('formAction');
    if (action) action.value = 'create';
    
    const id = document.getElementById('userId');
    if (id) id.value = '';
    
    const label = document.getElementById('userModalLabel');
    if (label) label.innerText = 'Tambah User Baru';
    
    const pass = document.getElementById('userPassword');
    if (pass) pass.required = true;

    const hint = document.querySelector('.id-hint');
    if (hint) hint.classList.add('d-none');

    // PERBAIKAN: Sembunyikan petunjuk foto saat tambah user baru
    const imgHint = document.querySelector('.image-hint');
    if (imgHint) imgHint.classList.add('d-none');
};

window.editUser = function(data) {
    window.clearForm();
    
    const action = document.getElementById('formAction');
    if (action) action.value = 'update';
    
    const id = document.getElementById('userId');
    if (id) id.value = data.id;
    
    const label = document.getElementById('userModalLabel');
    if (label) label.innerText = 'Edit Data User (ID: ' + data.id + ')';
    
    if (document.getElementById('userNama')) document.getElementById('userNama').value = data.nama;
    if (document.getElementById('userUsername')) document.getElementById('userUsername').value = data.username;
    if (document.getElementById('userEmail')) document.getElementById('userEmail').value = data.email;
    if (document.getElementById('userTelepon')) document.getElementById('userTelepon').value = data.telepon;
    if (document.getElementById('userStatus')) document.getElementById('userStatus').value = data.status;
    if (document.getElementById('userBuilding')) document.getElementById('userBuilding').value = data.building_id || '';
    if (document.getElementById('userFloor')) document.getElementById('userFloor').value = data.floor_id || '';
    if (document.getElementById('userRoom')) document.getElementById('userRoom').value = data.room_id || '';
    
    const pass = document.getElementById('userPassword');
    if (pass) pass.required = false;

    const hint = document.querySelector('.id-hint');
    if (hint) hint.classList.remove('d-none');

    // PERBAIKAN: Tampilkan petunjuk foto saat mengedit data user lama
    const imgHint = document.querySelector('.image-hint');
    if (imgHint) imgHint.classList.remove('d-none');
};
</script>

</body>
</html>
