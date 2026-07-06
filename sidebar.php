<?php
// Bagian session_start() sudah dihapus agar tidak bentrok dengan halaman utama

// ==================== PERBAIKAN OTOMATIS ====================
// 1. Ambil angka role_id dari session login (Super Admin = 1)
$sessionRoleId = isset($_SESSION['user']['role_id']) ? (int)$_SESSION['user']['role_id'] : 4;

// 2. Terjemahkan angka ID menjadi Nama Role teks agar dibaca oleh matriks di bawah
$roleMapping = [
    1 => 'Super Admin',
    2 => 'Admin IT',
    3 => 'Teknisi',
    4 => 'Viewer'
];

$userRole = isset($roleMapping[$sessionRoleId]) ? $roleMapping[$sessionRoleId] : 'Viewer';
// ===========================================================

// 2. Variabel pengaman untuk mendeteksi halaman aktif saat ini
$currentFile = basename($_SERVER['PHP_SELF']);

// 3. KONFIGURASI MATRIKS HAK AKSES MENU (Sesuai dokumen Anda)
$menuPermissions = [
    'dashboard.php'            => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'user.php'                 => ['Super Admin'],
    'roles.php'                => ['Super Admin'],
    'relasi.php'               => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'manajemen_asset.php'      => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'assets.php'               => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'asset_movements.php'      => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'server.php'               => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'network_device.php'       => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'vendors.php'              => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'password_categories.php'  => ['Super Admin', 'Admin IT', 'Teknisi'], // Viewer: X
    'password_vault.php'       => ['Super Admin', 'Admin IT', 'Teknisi'], // Viewer: X
    'tickets.php'              => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'maintenance.php'          => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'knowledge_articles.php'   => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'knowledge_categories.php' => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'sops.php'                 => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'software_licenses.php'    => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'backup_jobs.php'          => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'daily_checklist.php'      => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'activity_logs.php'        => ['Super Admin', 'Admin IT'] // Teknisi & Viewer: X
];

// 4. FUNGSI CEK ELEMEN MENU APAKAH BOLEH TAMPIL
if (!function_exists('hasMenuAccess')) {
    function hasMenuAccess($fileName, $currentRole, $matrix) {
        if (isset($matrix[$fileName])) {
            return in_array($currentRole, $matrix[$fileName]);
        }
        return true;
    }
}

// 5. FUNGSI DINAMIS UNTUK CEK HAK AKSES CRUD (TOMBOL AKSI FORM)
if (!function_exists('hasCrudAccess')) {
    function hasCrudAccess($fileName, $actionType, $currentRole) {
        // MATRIKS GLOBAL: Memetakan Hak Akses CRUD berdasarkan isi Dokumen Anda
        $crudMatrix = [
            'dashboard.php' => [
                'Super Admin' => ['R'], 'Admin IT' => ['R'], 'Teknisi' => ['R'], 'Viewer' => ['R']
            ],
            'user.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 'Admin IT' => [], 'Teknisi' => [], 'Viewer' => []
            ],
            'roles.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 'Admin IT' => [], 'Teknisi' => [], 'Viewer' => []
            ],
            'relasi.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 'Admin IT' => ['C', 'R', 'U', 'D'], 'Teknisi' => ['R'], 'Viewer' => ['R']
            ],
            'manajemen_asset.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 'Admin IT' => ['C', 'R', 'U', 'D'], 'Teknisi' => ['R'], 'Viewer' => ['R']
            ],
            'assets.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 'Admin IT' => ['C', 'R', 'U', 'D'], 'Teknisi' => ['R', 'U'], 'Viewer' => ['R']
            ],
            'asset_movements.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 'Admin IT' => ['C', 'R', 'U', 'D'], 'Teknisi' => ['C', 'R'], 'Viewer' => ['R']
            ],
            'server.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 'Admin IT' => ['C', 'R', 'U', 'D'], 'Teknisi' => ['R', 'U'], 'Viewer' => ['R']
            ],
            'network_device.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 'Admin IT' => ['C', 'R', 'U', 'D'], 'Teknisi' => ['R', 'U'], 'Viewer' => ['R']
            ],
            'vendors.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 'Admin IT' => ['C', 'R', 'U', 'D'], 'Teknisi' => ['R'], 'Viewer' => ['R']
            ],
            'password_vault.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 'Admin IT' => ['C', 'R', 'U', 'D'], 'Teknisi' => ['R'], 'Viewer' => []
            ],
            'tickets.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 'Admin IT' => ['C', 'R', 'U', 'D'], 'Teknisi' => ['C', 'R', 'U', 'D'], 'Viewer' => ['C', 'R']
            ],
            'maintenance.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 'Admin IT' => ['C', 'R', 'U', 'D'], 'Teknisi' => ['C', 'R', 'U', 'D'], 'Viewer' => ['R']
            ],
            'knowledge_articles.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 'Admin IT' => ['C', 'R', 'U', 'D'], 'Teknisi' => ['C', 'R', 'U'], 'Viewer' => ['R']
            ],
            'knowledge_categories.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 'Admin IT' => ['C', 'R', 'U', 'D'], 'Teknisi' => ['C', 'R', 'U'], 'Viewer' => ['R']
            ],
            'sops.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 'Admin IT' => ['C', 'R', 'U', 'D'], 'Teknisi' => ['R'], 'Viewer' => ['R']
            ],
            'software_licenses.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 'Admin IT' => ['C', 'R', 'U', 'D'], 'Teknisi' => ['R'], 'Viewer' => ['R']
            ],
            'backup_jobs.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 'Admin IT' => ['C', 'R', 'U', 'D'], 'Teknisi' => ['R', 'U'], 'Viewer' => ['R']
            ],
            'daily_checklist.php' => [
                'Super Admin' => ['C', 'R', 'U', 'D'], 'Admin IT' => ['C', 'R', 'U', 'D'], 'Teknisi' => ['C', 'R', 'U'], 'Viewer' => ['R']
            ],
            'activity_logs.php' => [
                'Super Admin' => ['R'], 'Admin IT' => ['R'], 'Teknisi' => [], 'Viewer' => []
            ]
        ];

        if (isset($crudMatrix[$fileName][$currentRole])) {
            return in_array($actionType, $crudMatrix[$fileName][$currentRole]);
        }
        return false;
    }
}
?>

<div class="flex-grow-1 menu-scroll-container w-100">
    <ul class="nav flex-column w-100">
      
        <!-- UTAMA (Tetap di Atas) -->
        <?php if (hasMenuAccess('dashboard.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?= checkActiveMenu('dashboard.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-house-door me-3"></i> Dashboard</a>
        </li>
        <?php endif; ?>

        <!-- URUTAN ABJAD A - Z -->
        
        <!-- Activity Logs / Log Aktivitas -->
        <?php if (hasMenuAccess('activity_logs.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item">
            <a href="activity_logs.php" class="nav-link <?= checkActiveMenu('activity_logs.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-clock-history me-3"></i> Activity Logs</a>
        </li>
        <?php endif; ?>

        <!-- Assets -->
        <?php if (hasMenuAccess('assets.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item">
            <a href="assets.php" class="nav-link <?= checkActiveMenu('assets.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-folder2-open me-3"></i> Assets</a>
        </li>
        <?php endif; ?>

        <!-- Asset Movements / Log Perpindahan -->
        <?php if (hasMenuAccess('asset_movements.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item">
            <a href="asset_movements.php" class="nav-link <?= checkActiveMenu('asset_movements.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-arrow-left-right me-3"></i> Asset Movements</a>
        </li>
        <?php endif; ?>

        <!-- Backup Jobs -->
        <?php if (hasMenuAccess('backup_jobs.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item"> 
            <a href="backup_jobs.php" class="nav-link <?= checkActiveMenu('backup_jobs.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-database-fill-gear me-3"></i> Backup Jobs</a> 
        </li>
        <?php endif; ?>

        <!-- Daily Checklist -->
        <?php if (hasMenuAccess('daily_checklist.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item">
            <a href="daily_checklist.php" class="nav-link <?= checkActiveMenu('daily_checklist.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-card-checklist me-3"></i> Daily Checklist</a>
        </li>
        <?php endif; ?>

        <!-- Knowledge Articles -->
        <?php if (hasMenuAccess('knowledge_articles.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item"> 
            <a href="knowledge_articles.php" class="nav-link <?= checkActiveMenu('knowledge_articles.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-file-earmark-text-fill me-3"></i> Knowledge Articles</a> 
        </li> 
        <?php endif; ?>

        <!-- Knowledge Categories -->
        <?php if (hasMenuAccess('knowledge_categories.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item"> 
            <a href="knowledge_categories.php" class="nav-link <?= checkActiveMenu('knowledge_categories.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-tags-fill me-3"></i> Knowledge Categories</a> 
        </li> 
        <?php endif; ?>

        <!-- Maintenance -->
        <?php if (hasMenuAccess('maintenance.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item">
            <a href="maintenance.php" class="nav-link <?= checkActiveMenu('maintenance.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-wrench-adjustable-circle me-3"></i> Maintenance</a>
        </li>
        <?php endif; ?>

        <!-- Manajemen Asset -->
        <?php if (hasMenuAccess('manajemen_asset.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item">
            <a href="manajemen_asset.php" class="nav-link <?= checkActiveMenu('manajemen_asset.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-boxes me-3"></i> Manajemen Asset</a>
        </li>
        <?php endif; ?>

        <!-- Manajemen Bangunan & Ruang / relasi.php -->
        <?php if (hasMenuAccess('relasi.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item">
            <a href="relasi.php" class="nav-link <?= checkActiveMenu('relasi.php', $currentFile) ?> rounded-end d-flex align-items-center text-truncate" title="Manajemen Bangunan & Ruang">
                <i class="bi bi-diagram-3 me-3 flex-shrink-0"></i> <span class="text-truncate">Manajemen Bangunan & Ruang</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Manajemen Roles / roles.php -->
        <?php if (hasMenuAccess('roles.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item">
            <a href="roles.php" class="nav-link <?= checkActiveMenu('roles.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-shield-lock me-3"></i> Manajemen Roles</a>
        </li>
        <?php endif; ?>

        <!-- Network Device -->
        <?php if (hasMenuAccess('network_device.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item">
            <a href="network_device.php" class="nav-link <?= checkActiveMenu('network_device.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-router me-3"></i> Network Device</a>
        </li>
        <?php endif; ?>

        <!-- Network Port -->
        <?php if (hasMenuAccess('network_device.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item">
            <a href="network_port.php" class="nav-link <?= checkActiveMenu('network_port.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-ethernet me-3"></i> Network Port</a>
        </li>
        <?php endif; ?>

        <!-- Password Categories -->
        <?php if (hasMenuAccess('password_categories.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item">
            <a href="password_categories.php" class="nav-link <?= checkActiveMenu('password_categories.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-grid-fill me-3"></i> Password Categories</a>
        </li>
        <?php endif; ?>

        <!-- Password Vault -->
        <?php if (hasMenuAccess('password_vault.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item">
            <a href="password_vault.php" class="nav-link <?= checkActiveMenu('password_vault.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-safe me-3"></i> Password Vault</a>
        </li>
        <?php endif; ?>

        <!-- Server -->
        <?php if (hasMenuAccess('server.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item">
            <a href="server.php" class="nav-link <?= checkActiveMenu('server.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-hdd-network me-3"></i> Server</a>
        </li>
        <?php endif; ?>

        <!-- Software Licenses -->
        <?php if (hasMenuAccess('software_licenses.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item"> 
            <a href="software_licenses.php" class="nav-link <?= checkActiveMenu('software_licenses.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-key-fill me-3"></i> Software Licenses</a> 
        </li> 
        <?php endif; ?>

        <!-- SOPS -->
        <?php if (hasMenuAccess('sops.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item"> 
            <a href="sops.php" class="nav-link <?= checkActiveMenu('sops.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-journal-text me-3"></i> SOPS</a> 
        </li> 
        <?php endif; ?>

        <!-- SOP Categories -->
        <?php if (hasMenuAccess('sop_categories.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item"> 
            <a href="sop_categories.php" class="nav-link <?= checkActiveMenu('sop_categories.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-tags me-3"></i> SOP Categories</a> 
        </li> 
        <?php endif; ?>

        <!-- Tickets / Tikets -->
        <?php if (hasMenuAccess('tickets.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item">
            <a href="tickets.php" class="nav-link <?= checkActiveMenu('tickets.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-ticket-perforated-fill me-3"></i> Tikets</a>
        </li>
        <?php endif; ?>

        <!-- Vendors -->
        <?php if (hasMenuAccess('vendors.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item">
            <a href="vendors.php" class="nav-link <?= checkActiveMenu('vendors.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-building me-3"></i> Vendors</a>
        </li>
        <?php endif; ?>

        <!-- PENUTUP -->
        <!-- User Profil -->
        <?php if (hasMenuAccess('user.php', $userRole, $menuPermissions)): ?>
        <li class="nav-item">
            <a href="user.php" class="nav-link <?= checkActiveMenu('user.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-person-fill me-3"></i> User Profil</a>
        </li>
        <?php endif; ?>
    </ul>
</div>

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
