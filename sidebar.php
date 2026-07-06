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

  <!-- Wadah Pembungkus Scroll Menu Sesuai Aturan CSS Anda -->
  <div class="menu-scroll-container flex-grow-1 w-100">
      <ul class="nav flex-column mb-auto list-unstyled w-100">

        <!-- 1. DASHBOARD -->
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?= checkActiveMenu('dashboard.php', $currentFile) ?> rounded d-flex align-items-center">
                <i class="bi bi-speedometer2 me-3"></i> Dashboard
            </a>
        </li>

        <!-- 2. GRUP USER (Management & Roles) -->
        <?php $isUserGroupActive = in_array($currentFile, ['user.php', 'roles.php']); ?>
        <li class="nav-item">
            <a href="#userSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $isUserGroupActive ? 'true' : 'false' ?>" 
               class="nav-link <?= $isUserGroupActive ? 'active-style' : '' ?> rounded d-flex align-items-center justify-content-between">
                <div>
                    <i class="bi bi-people me-3"></i> User
                </div>
                <i class="bi bi-chevron-down small"></i>
            </a>
            
            <div class="collapse <?= $isUserGroupActive ? 'show' : '' ?>" id="userSubmenu">
                <ul class="list-unstyled ps-4 my-1">
                    <li class="py-1">
                        <a href="user.php" class="nav-link <?= checkActiveMenu('user.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                            <i class="bi bi-person me-2"></i> User Management
                        </a>
                    </li>
                    <li class="py-1">
                        <a href="roles.php" class="nav-link <?= checkActiveMenu('roles.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                            <i class="bi bi-shield-lock me-2"></i> Manajemen Roles
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- 4. MANAJEMEN BANGUNAN & RUANG -->
        <li class="nav-item">
            <a href="relasi.php" class="nav-link <?= checkActiveMenu('relasi.php', $currentFile) ?> rounded d-flex align-items-center">
                <i class="bi bi-building me-3"></i> Manajemen Bangunan & Ruang
            </a>
        </li>

        <!-- 5, 6, 7. GRUP ASSET -->
        <?php $isAssetGroupActive = in_array($currentFile, ['manajemen_asset.php', 'assets.php', 'asset_movements.php']); ?>
        <li class="nav-item">
            <a href="#assetSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $isAssetGroupActive ? 'true' : 'false' ?>" 
               class="nav-link <?= $isAssetGroupActive ? 'active-style' : '' ?> rounded d-flex align-items-center justify-content-between">
                <div>
                    <i class="bi bi-boxes me-3"></i> Asset
                </div>
                <i class="bi bi-chevron-down small"></i>
            </a>
            
            <div class="collapse <?= $isAssetGroupActive ? 'show' : '' ?>" id="assetSubmenu">
                <ul class="list-unstyled ps-4 my-1">
                    <li class="py-1">
                        <a href="manajemen_asset.php" class="nav-link <?= checkActiveMenu('manajemen_asset.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                            <i class="bi bi-folder me-2"></i> Manajemen Asset
                        </a>
                    </li>
                    <li class="py-1">
                        <a href="assets.php" class="nav-link <?= checkActiveMenu('assets.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                            <i class="bi bi-laptop me-2"></i> Assets
                        </a>
                    </li>
                    <li class="py-1">
                        <a href="asset_movements.php" class="nav-link <?= checkActiveMenu('asset_movements.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                            <i class="bi bi-arrow-left-right me-2"></i> Asset Movements
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <!-- 8, 9, 10. GRUP NETWORK & SERVER -->
        <?php $isNetworkGroupActive = in_array($currentFile, ['server.php', 'network_device.php', 'network_port.php']); ?>
        <li class="nav-item">
            <a href="#networkSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $isNetworkGroupActive ? 'true' : 'false' ?>" 
               class="nav-link <?= $isNetworkGroupActive ? 'active-style' : '' ?> rounded d-flex align-items-center justify-content-between">
                <div>
                    <i class="bi bi-hdd-network me-3"></i> Network
                </div>
                <i class="bi bi-chevron-down small"></i>
            </a>
            
            <div class="collapse <?= $isNetworkGroupActive ? 'show' : '' ?>" id="networkSubmenu">
                <ul class="list-unstyled ps-4 my-1">
                    <li class="py-1">
                        <a href="server.php" class="nav-link <?= checkActiveMenu('server.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                            <i class="bi bi-server me-2"></i> Server
                        </a>
                    </li>
                    <li class="py-1">
                        <a href="network_device.php" class="nav-link <?= checkActiveMenu('network_device.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                            <i class="bi bi-cpu me-2"></i> Network Device
                        </a>
                    </li>
                    <li class="py-1">
                        <a href="network_port.php" class="nav-link <?= checkActiveMenu('network_port.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                            <i class="bi bi-ethernet me-2"></i> Network Port
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <!-- 11. VENDORS -->
        <li class="nav-item">
            <a href="vendors.php" class="nav-link <?= checkActiveMenu('vendors.php', $currentFile) ?> rounded d-flex align-items-center">
                <i class="bi bi-shop me-3"></i> Vendors
            </a>
        </li>

        <!-- 12, 13. GRUP PASSWORD -->
        <?php $isPasswordGroupActive = in_array($currentFile, ['password_categories.php', 'password_vault.php']); ?>
        <li class="nav-item">
            <a href="#passwordSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $isPasswordGroupActive ? 'true' : 'false' ?>" 
               class="nav-link <?= $isPasswordGroupActive ? 'active-style' : '' ?> rounded d-flex align-items-center justify-content-between">
                <div>
                    <i class="bi bi-key me-3"></i> Password
                </div>
                <i class="bi bi-chevron-down small"></i>
            </a>
            
            <div class="collapse <?= $isPasswordGroupActive ? 'show' : '' ?>" id="passwordSubmenu">
                <ul class="list-unstyled ps-4 my-1">
                    <li class="py-1">
                        <a href="password_categories.php" class="nav-link <?= checkActiveMenu('password_categories.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                            <i class="bi bi-folder-symlink me-2"></i> Password Categories
                        </a>
                    </li>
                    <li class="py-1">
                        <a href="password_vault.php" class="nav-link <?= checkActiveMenu('password_vault.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                            <i class="bi bi-lock me-2"></i> Password Vault
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <!-- 14. TIKETS -->
        <li class="nav-item">
            <a href="tickets.php" class="nav-link <?= checkActiveMenu('tickets.php', $currentFile) ?> rounded d-flex align-items-center">
                <i class="bi bi-ticket-perforated me-3"></i> Tikets
            </a>
        </li>

        <!-- 15. MAINTENANCE -->
        <li class="nav-item">
            <a href="maintenance.php" class="nav-link <?= checkActiveMenu('maintenance.php', $currentFile) ?> rounded d-flex align-items-center">
                <i class="bi bi-wrench-adjustable me-3"></i> Maintenance
            </a>
        </li>
        <!-- 16, 17. GRUP KNOWLEDGE / ARTIKEL -->
        <?php $isKnowledgeGroupActive = in_array($currentFile, ['knowledge_categories.php', 'knowledge_articles.php']); ?>
        <li class="nav-item">
            <a href="#knowledgeSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $isKnowledgeGroupActive ? 'true' : 'false' ?>" 
               class="nav-link <?= $isKnowledgeGroupActive ? 'active-style' : '' ?> rounded d-flex align-items-center justify-content-between">
                <div>
                    <i class="bi bi-journal-text me-3"></i> Artikel
                </div>
                <i class="bi bi-chevron-down small"></i>
            </a>
            
            <div class="collapse <?= $isKnowledgeGroupActive ? 'show' : '' ?>" id="knowledgeSubmenu">
                <ul class="list-unstyled ps-4 my-1">
                    <li class="py-1">
                        <a href="knowledge_categories.php" class="nav-link <?= checkActiveMenu('knowledge_categories.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                            <i class="bi bi-tags me-2"></i> Knowledge Categories
                        </a>
                    </li>
                    <li class="py-1">
                        <a href="knowledge_articles.php" class="nav-link <?= checkActiveMenu('knowledge_articles.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                            <i class="bi bi-file-earmark-text me-2"></i> Knowledge Articles
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <!-- 18, 19. GRUP SOP -->
        <?php $isSopGroupActive = in_array($currentFile, ['sop_categories.php', 'sops.php']); ?>
        <li class="nav-item">
            <a href="#sopSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $isSopGroupActive ? 'true' : 'false' ?>" 
               class="nav-link <?= $isSopGroupActive ? 'active-style' : '' ?> rounded d-flex align-items-center justify-content-between">
                <div>
                    <i class="bi bi-file-check me-3"></i> SOP
                </div>
                <i class="bi bi-chevron-down small"></i>
            </a>
            
            <div class="collapse <?= $isSopGroupActive ? 'show' : '' ?>" id="sopSubmenu">
                <ul class="list-unstyled ps-4 my-1">
                    <li class="py-1">
                        <a href="sop_categories.php" class="nav-link <?= checkActiveMenu('sop_categories.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                            <i class="bi bi-bookmark me-2"></i> SOP Categories
                        </a>
                    </li>
                    <li class="py-1">
                        <a href="sops.php" class="nav-link <?= checkActiveMenu('sops.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                            <i class="bi bi-file-earmark-check me-2"></i> SOPS
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- 20. SOFTWARE LICENSES -->
        <li class="nav-item">
            <a href="software_licenses.php" class="nav-link <?= checkActiveMenu('software_licenses.php', $currentFile) ?> rounded d-flex align-items-center">
                <i class="bi bi-patch-check me-3"></i> Software Licenses
            </a>
        </li>

        <!-- 21. BACKUP JOBS -->
        <li class="nav-item">
            <a href="backup_jobs.php" class="nav-link <?= checkActiveMenu('backup_jobs.php', $currentFile) ?> rounded d-flex align-items-center">
                <i class="bi bi-cloud-arrow-up me-3"></i> Backup Jobs
            </a>
        </li>

        <!-- 22. DAILY CHECKLIST -->
        <li class="nav-item">
            <a href="daily_checklist.php" class="nav-link <?= checkActiveMenu('daily_checklist.php', $currentFile) ?> rounded d-flex align-items-center">
                <i class="bi bi-card-checklist me-3"></i> Daily Checklist
            </a>
        </li>

        <!-- 23. ACTIVITY LOGS -->
        <li class="nav-item">
            <a href="activity_logs.php" class="nav-link <?= checkActiveMenu('activity_logs.php', $currentFile) ?> rounded d-flex align-items-center">
                <i class="bi bi-clock-history me-3"></i> Activity Logs
            </a>
        </li>
      </ul>
  </div> <!-- Penutup menu-scroll-container -->
</div> <!-- Penutup offcanvas-md / sidebarFlexible -->
