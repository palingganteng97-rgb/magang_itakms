<?php
require_once __DIR__ . '/auth.php';
require_login();

// Database config
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

// Pagination sederhana untuk tabel (CRUD)
$perPage = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Statistik tidak ditampilkan di sini, namun dibutuhkan untuk pagination.
    $stmtStats = $conn->prepare("SELECT COUNT(*) AS total_users FROM users");
    $stmtStats->execute();
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    $total_users = (int)($stats['total_users'] ?? 0);

    // Non aktif/aktif tidak digunakan di halaman ini (khusus manajemen user). 

    // Data tabel user
    $stmtUsers = $conn->prepare(
        "SELECT id, nama, username, email, telepon, status
         FROM users
         ORDER BY id DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmtUsers->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmtUsers->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtUsers->execute();

    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    // Pagination meta
    $totalPages = $perPage > 0 ? (int)ceil($total_users / $perPage) : 1;
} catch (PDOException $e) {
    echo "Koneksi gagal: " . $e->getMessage();
    die();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profil - ITAKMS</title>

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
                <li class="nav-item">
                    <a href="user.php" class="nav-link active p-2 rounded"><i class="bi bi-person-lines-fill me-2"></i> User Profil</a>
                </li>
                <li class="nav-item">
                    <a href="roles.php" class="nav-link p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Roles</a>
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

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-none d-md-flex flex-column sidebar p-3">
            <h4 class="text-center mb-4 text-warning"><i class="bi bi-speedometer2"></i> ITAKMS</h4>
            <ul class="nav flex-column gap-2">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="user.php" class="nav-link active p-2 rounded"><i class="bi bi-person-lines-fill me-2"></i> User Profil</a>
                </li>
                <li class="nav-item">
                    <a href="roles.php" class="nav-link p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Roles</a>
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

        <main class="col-md-9 col-12 px-2 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">User Profil</h1>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary p-2">Sesi Admin</span>
                </div>
            </div>



            <!-- CRUD table -->
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex flex-column flex-sm-row gap-2 justify-content-between align-items-sm-center">
                    <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-table me-2"></i> Tabel Manajemen Pengguna</h5>
                    <button type="button" class="btn btn-sm btn-dark" id="btnAddUser">
                        <i class="bi bi-plus-lg me-1"></i> Tambah User
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Telepon</th>
                                    <th>Status</th>
                                    <th style="width: 120px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($total_users > 0): ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td class="fw-bold">#<?= htmlspecialchars($user['id']) ?></td>
                                            <td><?= htmlspecialchars($user['nama']) ?></td>
                                            <td><code><?= htmlspecialchars($user['username']) ?></code></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><?= htmlspecialchars($user['telepon']) ?></td>
                                            <td>
                                                <span class="badge <?= $user['status'] == 1 ? 'bg-success-subtle text-success border border-success' : 'bg-danger-subtle text-danger border border-danger' ?> px-2.5 py-1.5">
                                                    <?= $user['status'] == 1 ? 'Aktif' : 'Non-Aktif' ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-primary btnEditUser"
                                                    data-id="<?= htmlspecialchars($user['id']) ?>"
                                                    data-nama="<?= htmlspecialchars($user['nama']) ?>"
                                                    data-username="<?= htmlspecialchars($user['username']) ?>"
                                                    data-email="<?= htmlspecialchars($user['email']) ?>"
                                                    data-telepon="<?= htmlspecialchars($user['telepon']) ?>"
                                                    data-status="<?= (int)$user['status'] ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger btnDeleteUser" data-id="<?= htmlspecialchars($user['id']) ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Belum ada data di tabel users.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_users > 0): ?>
                        <div class="card-footer bg-white">
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-end mb-0">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="user.php?page=<?= max(1, $page - 1) ?>" tabindex="-1">&laquo; Prev</a>
                                    </li>

                                    <?php
                                    $start = max(1, $page - 2);
                                    $end = min($totalPages, $page + 2);
                                    for ($p = $start; $p <= $end; $p++):
                                    ?>
                                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="user.php?page=<?= $p ?>"><?= $p ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="user.php?page=<?= min($totalPages, $page + 1) ?>">Next &raquo;</a>
                                    </li>
                                </ul>
                            </nav>

                            <div class="text-muted small mt-2">
                                Menampilkan <?= count($users) ?> dari <?= $total_users ?> user (halaman <?= $page ?> / <?= max(1, $totalPages) ?>)
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<?php include 'users_modal.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnAddUser = document.getElementById('btnAddUser');
        const userModalEl = document.getElementById('userModal');
        const userModal = userModalEl ? new bootstrap.Modal(userModalEl) : null;

        const form = document.getElementById('userForm');
        const formAction = document.getElementById('formAction');
        const formId = document.getElementById('formId');
        const fieldNama = document.getElementById('fieldNama');
        const fieldUsername = document.getElementById('fieldUsername');
        const fieldEmail = document.getElementById('fieldEmail');
        const fieldTelepon = document.getElementById('fieldTelepon');
        const fieldStatus = document.getElementById('fieldStatus');
        const formError = document.getElementById('formError');

        function setError(msg) {
            if (!formError) return;
            if (!msg) {
                formError.classList.add('d-none');
                formError.textContent = '';
                return;
            }
            formError.textContent = msg;
            formError.classList.remove('d-none');
        }

        function resetForm() {
            if (!form) return;
            form.reset();
            if (formAction) formAction.value = 'create';
            if (formId) formId.value = '';
            setError('');
        }

        if (btnAddUser && userModal) {
            btnAddUser.addEventListener('click', () => {
                resetForm();
                if (fieldStatus) fieldStatus.value = '1';
                userModal.show();
            });
        }

        document.querySelectorAll('.btnEditUser').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id') || '';
                const nama = btn.getAttribute('data-nama') || '';
                const username = btn.getAttribute('data-username') || '';
                const email = btn.getAttribute('data-email') || '';
                const telepon = btn.getAttribute('data-telepon') || '';
                const status = btn.getAttribute('data-status') || '0';

                if (formAction) formAction.value = 'update';
                if (formId) formId.value = id;
                if (fieldNama) fieldNama.value = nama;
                if (fieldUsername) fieldUsername.value = username;
                if (fieldEmail) fieldEmail.value = email;
                if (fieldTelepon) fieldTelepon.value = telepon;
                if (fieldStatus) fieldStatus.value = String(status);

                setError('');
                userModal.show();
            });
        });

        document.querySelectorAll('.btnDeleteUser').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                if (!id) return;
                if (!confirm('Hapus user ID ' + id + '?')) return;

                const fd = new FormData();
                fd.append('action', 'delete');
                fd.append('id', id);

                try {
                    const res = await fetch('crud_users.php', {
                        method: 'POST',
                        body: fd
                    });
                    const json = await res.json();
                    if (!json.ok) {
                        alert(json.message || 'Gagal menghapus');
                        return;
                    }
                    location.reload();
                } catch (e) {
                    alert('Gagal menghapus');
                }
            });
        });

        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                setError('');

                const fd = new FormData(form);
                const action = fd.get('action');
                if (!action) fd.set('action', 'create');

                try {
                    const res = await fetch('crud_users.php', {
                        method: 'POST',
                        body: fd
                    });
                    const json = await res.json();

                    if (!json.ok) {
                        setError(json.message || 'Gagal menyimpan');
                        return;
                    }

                    userModal.hide();
                    location.reload();
                } catch (err) {
                    setError('Gagal menyimpan');
                }
            });
        }
    });
</script>
</body>
</html>

