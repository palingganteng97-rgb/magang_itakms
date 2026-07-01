<?php
require_once __DIR__ . '/auth.php';
require_login();

// Database config
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $stmt = $conn->prepare("SELECT id, nama, keterangan, status FROM roles ORDER BY id ASC");
    $stmt->execute();
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    http_response_code(500);
    echo "Koneksi gagal: " . htmlspecialchars($e->getMessage());
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles - ITAKMS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background-color: #212529; color: white; }
        .sidebar a { color: #adb5bd; text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { color: white; background-color: #343a40; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-dark d-md-none px-3 shadow">
    <div class="d-flex align-items-center justify-content-between w-100">
        <span class="navbar-brand text-warning fw-bold"><i class="bi bi-speedometer2"></i> ITAKMS</span>
        <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
            <i class="bi bi-list"></i>
        </button>
    </div>
</nav>

<!-- SIDEBAR MOBILE (OFFCANVAS) KHUSUS UNTUK ROLES.PHP -->
<div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="mobileSidebarLabel"><i class="bi bi-speedometer2"></i> ITAKMS</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0">
    <nav class="sidebar p-3 d-flex flex-column" style="min-height: calc(100vh - 56px);">
      <ul class="nav flex-column gap-2">
        <li class="nav-item">
          <a href="dashboard.php" class="nav-link p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
        </li>
        <!-- Manajemen Roles Aktif di Mobile -->
        <li class="nav-item">
          <a href="roles.php" class="nav-link active bg-primary text-white p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Manajemen Roles</a>
        </li>
        <li class="nav-item">
          <a href="relasi.php" class="nav-link p-2 rounded text-nowrap" style="overflow: hidden; text-overflow: ellipsis;">
            <i class="bi bi-diagram-3 me-2"></i> Manajemen Bangunan & Ruang
          </a>
        </li>
        <li class="nav-item">
          <a href="assets.php" class="nav-link p-2 rounded"><i class="bi bi-folder2-open me-2"></i> Assets</a>
        </li>
        <li class="nav-item">
          <a href="manajemen_asset.php" class="nav-link p-2 rounded"><i class="bi bi-boxes me-2"></i> Manajemen Asset</a>
        </li>
        <li class="nav-item">
          <a href="asset_movements.php" class="nav-link p-2 rounded"><i class="bi bi-arrow-left-right me-2"></i> Log Perpindahan</a>
        </li>
        <li class="nav-item">
          <a href="server.php" class="nav-link p-2 rounded"><i class="bi bi-hdd-network me-2"></i> Server</a>
        </li>
        <!-- Menu Network Device (Mobile) -->
        <li class="nav-item">
          <a href="network_device.php" class="nav-link p-2 rounded"><i class="bi bi-router me-2"></i> Network Device</a>
        </li>
        <!-- Menu Network Port (Mobile) -->
        <li class="nav-item">
          <a href="network_port.php" class="nav-link p-2 rounded"><i class="bi bi-ethernet me-2"></i> Network Port</a>
        </li>
        <li class="nav-item">
          <a href="user.php" class="nav-link p-2 rounded"><i class="bi bi-person-lines-fill me-2"></i> User Profil</a>
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

<!-- SIDEBAR DESKTOP KHUSUS UNTUK ROLES.PHP -->
<div class="container-fluid">
  <div class="row">
    <nav class="col-md-4 col-lg-3 d-none d-md-flex flex-column sidebar p-3 text-bg-dark" style="min-height: 100vh;">
      <h4 class="text-center mb-4 text-warning"><i class="bi bi-speedometer2"></i> ITAKMS</h4>
      <ul class="nav flex-column gap-2">
        <li class="nav-item">
          <a href="dashboard.php" class="nav-link p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
        </li>
        <!-- Manajemen Roles Aktif di Desktop -->
        <li class="nav-item">
          <a href="roles.php" class="nav-link active bg-primary text-white p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Manajemen Roles</a>
        </li>
        <li class="nav-item">
          <a href="relasi.php" class="nav-link p-2 rounded text-nowrap" style="overflow: hidden; text-overflow: ellipsis;" title="Manajemen Bangunan & Ruang">
            <i class="bi bi-diagram-3 me-2"></i> Manajemen Bangunan & Ruang
          </a>
        </li>
        <li class="nav-item">
          <a href="assets.php" class="nav-link p-2 rounded"><i class="bi bi-folder2-open me-2"></i> Assets</a>
        </li>
        <li class="nav-item">
          <a href="manajemen_asset.php" class="nav-link p-2 rounded"><i class="bi bi-boxes me-2"></i> Manajemen Asset</a>
        </li>
        <li class="nav-item">
          <a href="asset_movements.php" class="nav-link p-2 rounded"><i class="bi bi-arrow-left-right me-2"></i> Log Perpindahan</a>
        </li>
        <li class="nav-item">
          <a href="server.php" class="nav-link p-2 rounded"><i class="bi bi-hdd-network me-2"></i> Server</a>
        </li>
        <!-- Menu Network Device (Desktop) -->
        <li class="nav-item">
          <a href="network_device.php" class="nav-link p-2 rounded"><i class="bi bi-router me-2"></i> Network Device</a>
        </li>
        <!-- Menu Network Port (Desktop) -->
        <li class="nav-item">
          <a href="network_port.php" class="nav-link p-2 rounded"><i class="bi bi-ethernet me-2"></i> Network Port</a>
        </li>
        <li class="nav-item">
          <a href="user.php" class="nav-link p-2 rounded"><i class="bi bi-person-lines-fill me-2"></i> User Profil</a>
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

    <!-- Main content -->
        <main class="col-md-9 col-12 px-2 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manajemen Roles</h1>
                <span class="badge bg-secondary p-2">Sesi Admin</span>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex flex-column flex-sm-row gap-2 justify-content-between align-items-sm-center">
                    <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-shield-lock me-2"></i> Tabel Manajemen Roles</h5>
                    <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#modalTambahRole">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Role
                    </button>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                            <tr>
                                <th style="width: 90px;">ID</th>
                                <th>Nama</th>
                                <th>Keterangan</th>
                                <th>Status</th>
                                <th style="width: 140px;">Aksi</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (count($roles) > 0): ?>
                                <?php foreach ($roles as $role): ?>
                                    <tr>
                                        <td class="fw-bold">#<?= (int)$role['id'] ?></td>
                                        <td>
                                            <span class="badge bg-dark px-2.5 py-1.5"><?= htmlspecialchars($role['nama']) ?></span>
                                        </td>
                                        <td class="text-secondary small"><?= htmlspecialchars($role['keterangan'] ?? '-') ?></td>
                                        <td>
                                            <?php if ((int)$role['status'] === 1): ?>
                                                <span class="badge bg-success-subtle text-success border border-success px-2.5 py-1.5">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger px-2.5 py-1.5">Non-Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary btnEditRole"
                                                data-id="<?= (int)$role['id'] ?>"
                                                data-nama="<?= htmlspecialchars($role['nama']) ?>"
                                                data-keterangan="<?= htmlspecialchars($role['keterangan'] ?? '') ?>"
                                                data-status="<?= (int)$role['status'] ?>"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditRole"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger btnDeleteRole"
                                                data-id="<?= (int)$role['id'] ?>"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Belum ada data roles.</td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Tambah Role -->
<div class="modal fade" id="modalTambahRole" tabindex="-1" aria-labelledby="modalTambahRoleLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formTambahRole" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahRoleLabel">Tambah Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="action" value="create">

                    <!-- id auto increment di DB; nama wajib -->
                    <div class="mb-3">
                        <label class="form-label">Nama Peran</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>

                    <!-- keterangan NULL default -->
                    <div class="mb-3">
                        <label class="form-label">Keterangan (opsional)</label>
                        <textarea name="keterangan" class="form-control" rows="3"></textarea>
                    </div>

                    <!-- status default '1' -->
                    <input type="hidden" name="status" value="1">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Role -->
<div class="modal fade" id="modalEditRole" tabindex="-1" aria-labelledby="modalEditRoleLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditRole" action="crud_roles.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditRoleLabel">Edit Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editId">

                    <div class="mb-3">
                        <label class="form-label">Nama Peran</label>
                        <input type="text" name="nama" id="editNama" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan (opsional)</label>
                        <textarea name="keterangan" id="editKeterangan" class="form-control" rows="3"></textarea>
                    </div>

                    <!-- status bisa diedit sesuai data (default 1 di insert) -->
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="editStatus" class="form-select" required>
                            <option value="1">Aktif</option>
                            <option value="0">Non-Aktif</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Isi modal edit
        const modalEdit = document.getElementById('modalEditRole');
        modalEdit.addEventListener('show.bs.modal', (event) => {
            const btn = event.relatedTarget;
            if (!btn) return;
            document.getElementById('editId').value = btn.getAttribute('data-id');
            document.getElementById('editNama').value = btn.getAttribute('data-nama');
            document.getElementById('editKeterangan').value = btn.getAttribute('data-keterangan');
            document.getElementById('editStatus').value = btn.getAttribute('data-status');
        });

        // Supaya tidak tampil JSON mentah saat submit form (karena crud_roles.php mengembalikan JSON)
        // Kita intercept submit dan redirect balik ke roles.php setelah sukses.
        const ajaxSubmit = (form) => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(form);

                try {
                    const res = await fetch('crud_roles.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (data.ok) {
                        alert(data.message);
                        window.location.href = 'roles.php';
                    } else {
                        alert('Gagal: ' + (data.message || 'Unknown error'));
                    }
                } catch (err) {
                    console.error(err);
                    alert('Gagal menghubungi server.');
                }
            });
        };

        ajaxSubmit(document.getElementById('formTambahRole'));
        ajaxSubmit(document.getElementById('formEditRole'));

        // Delete via AJAX
        document.querySelectorAll('.btnDeleteRole').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                if (!confirm('Apakah yakin menghapus role ini?')) return;

                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                try {
                    const res = await fetch('crud_roles.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (data.ok) {
                        alert(data.message);
                        window.location.href = 'roles.php';
                    } else {
                        alert('Gagal menghapus: ' + (data.message || 'Unknown error'));
                    }
                } catch (err) {
                    console.error(err);
                    alert('Gagal menghubungi server.');
                }
            });
        });
    });
</script>

</body>
</html>

