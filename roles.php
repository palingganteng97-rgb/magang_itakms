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
                display: block !important; /* Kembalikan sifat block di laptop */
            }
            .offcanvas-backdrop, .offcanvas-backdrop.show {
                display: none !important;
                opacity: 0 !important;
                visibility: hidden !important;
                pointer-events: none !important;
            }
        }

        /* ==================================================== */
        /* CONFIG LAYOUT MOBILE / HP (Lebar Layar < 768px)     */
        /* ==================================================== */
        @media (max-width: 767.98px) {
            body {
                overflow: auto !important;
                position: relative !important;
                display: block !important; /* Hancurkan flexbox desktop di HP */
            }
            /* Kunci container induk luar agar di HP melepas kuncian flex */
            .d-md-flex {
                display: block !important; 
            }
            .sidebar-fixed {
                position: fixed !important;
                width: 280px !important;
                height: 100vh !important;
            }
            .menu-scroll-container {
                max-height: none !important;
                overflow-y: visible !important;
            }
            /* SOLUSI UTAMA: Hancurkan pembatasan tinggi pendek di HP */
            .main-content {
                width: 100% !important;
                margin-left: 0 !important;
                min-height: calc(100vh - 70px) !important; /* Ambil sisa tinggi layar HP */
                display: block !important;
                height: auto !important; /* Biarkan memanjang alami mengikuti jumlah data tabel */
            }
            .offcanvas-backdrop.show {
                display: block !important;
                opacity: 0 !important;
                background-color: transparent !important;
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
  
  <!-- 3. AREA MENU TENGAH (Otomatis & Fleksibel untuk Semua Opsi Berbasis Fungsi PHP) -->
  <?php
  // Ambil nama file yang sedang aktif saat ini secara real-time
  $currentFile = basename($_SERVER['PHP_SELF']);

  // Fungsi pintar untuk otomatis mencetak class 'active-style' jika nama file cocok
  if (!function_exists('checkActiveMenu')) {
      function checkActiveMenu($targetFile, $currentFile) {
          return ($currentFile === $targetFile) ? 'active-style' : '';
      }
  }
  ?>
  
  <div class="flex-grow-1 menu-scroll-container w-100">
    <ul class="nav flex-column w-100">
      
      <!-- UTAMA -->
        <li class="nav-item">
        <a href="dashboard.php" class="nav-link <?= checkActiveMenu('dashboard.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-house-door me-3"></i> Dashboard</a>
        </li>

        <!-- URUTAN ABJAD A - Z -->
        <li class="nav-item">
        <a href="assets.php" class="nav-link <?= checkActiveMenu('assets.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-folder2-open me-3"></i> Assets</a>
        </li>

        <li class="nav-item"> 
        <a href="backup_jobs.php" class="nav-link <?= checkActiveMenu('backup_jobs.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-database-fill-gear me-3"></i> Backup Jobs</a> 
        </li>

        <li class="nav-item">
        <a href="daily_checklist.php" class="nav-link <?= checkActiveMenu('daily_checklist.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-card-checklist me-3"></i> Daily Checklist</a>
        </li>

        <li class="nav-item"> 
        <a href="knowledge_articles.php" class="nav-link <?= checkActiveMenu('knowledge_articles.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-file-earmark-text-fill me-3"></i> Knowledge Articles</a> 
        </li> 

        <li class="nav-item"> 
        <a href="knowledge_categories.php" class="nav-link <?= checkActiveMenu('knowledge_categories.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-tags-fill me-3"></i> Knowledge Categories</a> 
        </li> 

        <li class="nav-item">
        <a href="activity_logs.php" class="nav-link <?= checkActiveMenu('activity_logs.php', $currentFile) ?> rounded-end d-flex align-items-center">
            <i class="bi bi-clock-history me-3"></i> Log Aktivitas
        </a>
        </li>

        <li class="nav-item">
        <a href="asset_movements.php" class="nav-link <?= checkActiveMenu('asset_movements.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-arrow-left-right me-3"></i> Log Perpindahan</a>
        </li>

        <li class="nav-item">
        <a href="maintenance.php" class="nav-link <?= checkActiveMenu('maintenance.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-wrench-adjustable-circle me-3"></i> Maintenance</a>
        </li>

        <li class="nav-item">
        <a href="manajemen_asset.php" class="nav-link <?= checkActiveMenu('manajemen_asset.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-boxes me-3"></i> Manajemen Asset</a>
        </li>

        <li class="nav-item">
        <a href="relasi.php" class="nav-link <?= checkActiveMenu('relasi.php', $currentFile) ?> rounded-end d-flex align-items-center text-truncate" title="Manajemen Bangunan & Ruang">
            <i class="bi bi-diagram-3 me-3 flex-shrink-0"></i> <span class="text-truncate">Manajemen Bangunan & Ruang</span>
        </a>
        </li>

        <li class="nav-item">
        <a href="roles.php" class="nav-link <?= checkActiveMenu('roles.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-shield-lock me-3"></i> Manajemen Roles</a>
        </li>

        <li class="nav-item">
        <a href="network_device.php" class="nav-link <?= checkActiveMenu('network_device.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-router me-3"></i> Network Device</a>
        </li>

        <li class="nav-item">
        <a href="network_port.php" class="nav-link <?= checkActiveMenu('network_port.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-ethernet me-3"></i> Network Port</a>
        </li>

        <li class="nav-item">
        <a href="password_categories.php" class="nav-link <?= checkActiveMenu('password_categories.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-grid-fill me-3"></i> Password Categories</a>
        </li>

        <li class="nav-item">
        <a href="password_vault.php" class="nav-link <?= checkActiveMenu('password_vault.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-safe me-3"></i> Password Vault</a>
        </li>

        <li class="nav-item">
        <a href="server.php" class="nav-link <?= checkActiveMenu('server.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-hdd-network me-3"></i> Server</a>
        </li>

        <li class="nav-item"> 
        <a href="software_licenses.php" class="nav-link <?= checkActiveMenu('software_licenses.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-key-fill me-3"></i> Software Licenses</a> 
        </li> 

        <li class="nav-item"> 
        <a href="sop_categories.php" class="nav-link <?= checkActiveMenu('sop_categories.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-tags me-3"></i> SOP Categories</a> 
        </li> 

        <li class="nav-item"> 
        <a href="sops.php" class="nav-link <?= checkActiveMenu('sops.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-journal-text me-3"></i> SOPS</a> 
        </li> 

        <li class="nav-item">
        <a href="tickets.php" class="nav-link <?= checkActiveMenu('tickets.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-ticket-perforated-fill me-3"></i> Tikets</a>
        </li>

        <li class="nav-item">
        <a href="vendors.php" class="nav-link <?= checkActiveMenu('vendors.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-building me-3"></i> Vendors</a>
        </li>

        <!-- PENUTUP -->
        <li class="nav-item">
        <a href="user.php" class="nav-link <?= checkActiveMenu('user.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-person-fill me-3"></i> User Profil</a>
        </li>

    </ul>
  </div> <!-- /penutup area menu tengah -->
  
  <!-- 4. Tombol Logout (Mengunci di Dasar Laci Menu) -->
  <div class="mt-auto pt-3 border-top border-secondary bg-dark w-100 px-3">
    <ul class="nav flex-column w-100">
      <li class="nav-item w-100">
        <a href="logout.php" 
           class="nav-link d-inline-flex align-items-center py-2 px-3 rounded w-100 logout-btn-merah" 
           style="color: #dc3545 !important; font-weight: 600 !important; transition: all 0.2s ease-in-out; box-sizing: border-box;">
          <i class="bi bi-box-arrow-right me-3 fs-5" style="color: #dc3545 !important;"></i> 
          <span>Logout</span>
        </a>
      </li>
    </ul>
  </div>

</div> <!-- /penutup elemen offcanvas-md -->

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
