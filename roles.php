<?php
require_once __DIR__ . '/auth.php';
require_login();

// SISIPKAN PROTEKSI KETAT RBAC DI SINI
require_once __DIR__ . '/helper_rbac.php';

// Berdasarkan matriks, modul Role Management hanya boleh diakses oleh Super Admin (ID 1)
if (!isset($_SESSION['user_role_id']) || (int)$_SESSION['user_role_id'] !== 1) {
    header('HTTP/1.1 403 Forbidden');
    echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>";
    echo "<h2>403 - Akses Ditolak</h2>";
    echo "<p>Halaman Role Management ini hanya dapat diakses oleh Super Admin.</p>";
    echo "<a href='dashboard.php'>Kembali ke Dashboard</a>";
    echo "</div>";
    exit;
}

// Database config
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

try {
    // SINKRONISASI: Menambahkan charset=utf8mb4 agar kompatibel penuh dengan pembacaan karakter khusus
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password, [
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

    <?php include 'footer-admin.php'; ?>
