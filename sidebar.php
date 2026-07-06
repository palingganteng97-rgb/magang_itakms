<?php
// =========================================================================
// 1. OTENTIKASI & TRANSLASI ID ROLE MENJADI TEKS DINAMIS
// =========================================================================
// session_start() SUDAH DIHAPUS SEPENUHNYA DARI SINI AGAR TIDAK BENTROK DENGAN AUTH.PHP
$sessionRoleId = isset($_SESSION['user']['role_id']) ? (int)$_SESSION['user']['role_id'] : 4;

$roleMapping = [
    1 => 'Super Admin',
    2 => 'Admin IT',
    3 => 'Teknisi',
    4 => 'Viewer'
];

$userRole = isset($roleMapping[$sessionRoleId]) ? $roleMapping[$sessionRoleId] : 'Viewer';

// Menangkap nama file aktif saat ini untuk pencocokan kelas active-style
$currentFile = basename($_SERVER['PHP_SELF']);

// =========================================================================
// 2. MATRIKS HAK AKSES MENU UTAMA (Sesuai Aturan Resmi Dokumen Anda)
// =========================================================================
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
    'password_categories.php'  => ['Super Admin', 'Admin IT', 'Teknisi'], 
    'password_vault.php'       => ['Super Admin', 'Admin IT', 'Teknisi'], 
    'tickets.php'              => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'maintenance.php'          => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'knowledge_articles.php'   => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'knowledge_categories.php' => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'sops.php'                 => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'software_licenses.php'    => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'backup_jobs.php'          => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'daily_checklist.php'      => ['Super Admin', 'Admin IT', 'Teknisi', 'Viewer'],
    'activity_logs.php'        => ['Super Admin', 'Admin IT'] 
];

// =========================================================================
// 4. FUNGSI UTILITAS PENGECEKAN HAK AKSES DAN KELAS AKTIF VISUAL
// =========================================================================
if (!function_exists('hasMenuAccess')) {
    function hasMenuAccess($fileName, $currentRole, $matrix) {
        if (isset($matrix[$fileName])) {
            return in_array($currentRole, $matrix[$fileName]);
        }
        return true;
    }
}

if (!function_exists('checkActiveMenu')) {
    function checkActiveMenu($fileName, $currentFile) {
        $buildingPages = ['relasi.php', 'relasi_gedung.php', 'relasi_lantai.php', 'relasi_ruangan.php'];
        if (in_array($fileName, $buildingPages) && in_array($currentFile, $buildingPages)) {
            return 'active-style';
        }
        return ($fileName === $currentFile) ? 'active-style' : '';
    }
}

// =========================================================================
// 5. MATRIKS GLOBAL OTORISASI CRUD (Pencegah Fatal Error)
// =========================================================================
if (!function_exists('hasCrudAccess')) {
    function hasCrudAccess($fileName, $actionType, $currentRole) {
        $cleanFileName = basename($fileName);
        
        if (in_array($cleanFileName, ['relasi_gedung.php', 'relasi_lantai.php', 'relasi_ruangan.php'])) {
            $cleanFileName = 'relasi.php';
        }
        if ($cleanFileName === 'vendor_apis.php') {
            $cleanFileName = 'vendors.php';
        }

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

        if (isset($crudMatrix[$cleanFileName][$currentRole])) {
            return in_array($actionType, $crudMatrix[$cleanFileName][$currentRole]);
        }
        return false;
    }
}

// =========================================================================
// 6. LOGIKA AUTO-OPEN DROPDOWN COLLAPSE SIDEBAR
// =========================================================================
$isUserGroupActive      = in_array($currentFile, ['user.php', 'roles.php']);
$isAssetGroupActive     = in_array($currentFile, ['manajemen_asset.php', 'assets.php', 'asset_movements.php']);
$isNetworkGroupActive   = in_array($currentFile, ['server.php', 'network_device.php', 'network_port.php']);
$isPasswordGroupActive  = in_array($currentFile, ['password_categories.php', 'password_vault.php']);
$isKnowledgeGroupActive = in_array($currentFile, ['knowledge_categories.php', 'knowledge_articles.php']);
$isSopGroupActive       = in_array($currentFile, ['sop_categories.php', 'sops.php']);

$buildingPages          = ['relasi.php', 'relasi_gedung.php', 'relasi_lantai.php', 'relasi_ruangan.php'];
$isBuildingActive       = in_array($currentFile, $buildingPages);
?>

<!-- 1. DASHBOARD -->
<?php if (hasMenuAccess('dashboard.php', $userRole, $menuPermissions)): ?>
<li class="nav-item mb-1">
    <a href="dashboard.php" class="nav-link <?= checkActiveMenu('dashboard.php', $currentFile) ?> rounded d-flex align-items-center">
        <i class="bi bi-speedometer2 me-3"></i> Dashboard
    </a>
</li>
<?php endif; ?>

<!-- 2 & 3. GRUP USER -->
<?php if (hasMenuAccess('user.php', $userRole, $menuPermissions) || hasMenuAccess('roles.php', $userRole, $menuPermissions)): ?>
<li class="nav-item mb-1">
    <a href="#userSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $isUserGroupActive ? 'true' : 'false' ?>" 
       class="nav-link <?= $isUserGroupActive ? 'active-style' : '' ?> rounded d-flex align-items-center justify-content-between">
        <div><i class="bi bi-people me-3"></i> User</div>
        <i class="bi bi-chevron-down small"></i>
    </a>
    <div class="collapse <?= $isUserGroupActive ? 'show' : '' ?>" id="userSubmenu">
        <ul class="list-unstyled ps-4 my-1">
            <?php if (hasMenuAccess('user.php', $userRole, $menuPermissions)): ?>
            <li class="py-1">
                <a href="user.php" class="nav-link <?= checkActiveMenu('user.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                    <i class="bi bi-person me-2"></i> User Management
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasMenuAccess('roles.php', $userRole, $menuPermissions)): ?>
            <li class="py-1">
                <a href="roles.php" class="nav-link <?= checkActiveMenu('roles.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                    <i class="bi bi-shield-lock me-2"></i> Role Management
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</li>
<?php endif; ?>

<!-- 4. MANAJEMEN BANGUNAN & RUANG (FIX: Menyamakan kelas ke active-style) -->
<?php 
// Daftarkan semua file yang berhubungan dengan manajemen Gedung/Lantai/Ruangan
$buildingPages = ['relasi.php', 'relasi_gedung.php', 'relasi_lantai.php', 'relasi_ruangan.php'];
$isBuildingActive = in_array($currentFile, $buildingPages);
?>

<?php if (hasMenuAccess('relasi.php', $userRole, $menuPermissions)): ?>
<li class="nav-item mb-1">
    <!-- PERUBAHAN: Kelas diubah dari 'active' menjadi 'active-style' agar sesuai dengan standar CSS sidebar Anda -->
    <a href="relasi.php" class="nav-link <?= $isBuildingActive ? 'active-style' : '' ?> rounded d-flex align-items-center">
        <i class="bi bi-building me-3"></i> Gedung/Lantai/Ruangan
    </a>
</li>
<?php endif; ?>

<!-- 5, 6, 7. GRUP ASSET -->
<?php if (hasMenuAccess('manajemen_asset.php', $userRole, $menuPermissions) || hasMenuAccess('assets.php', $userRole, $menuPermissions) || hasMenuAccess('asset_movements.php', $userRole, $menuPermissions)): ?>
<li class="nav-item mb-1">
    <a href="#assetSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $isAssetGroupActive ? 'true' : 'false' ?>" 
       class="nav-link <?= $isAssetGroupActive ? 'active-style' : '' ?> rounded d-flex align-items-center justify-content-between">
        <div><i class="bi bi-boxes me-3"></i> Asset</div>
        <i class="bi bi-chevron-down small"></i>
    </a>
    <div class="collapse <?= $isAssetGroupActive ? 'show' : '' ?>" id="assetSubmenu">
        <ul class="list-unstyled ps-4 my-1">
            <?php if (hasMenuAccess('manajemen_asset.php', $userRole, $menuPermissions)): ?>
            <li class="py-1">
                <a href="manajemen_asset.php" class="nav-link <?= checkActiveMenu('manajemen_asset.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                    <i class="bi bi-folder me-2"></i> Management Assets
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasMenuAccess('assets.php', $userRole, $menuPermissions)): ?>
            <li class="py-1">
                <a href="assets.php" class="nav-link <?= checkActiveMenu('assets.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                    <i class="bi bi-laptop me-2"></i> Assets
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasMenuAccess('asset_movements.php', $userRole, $menuPermissions)): ?>
            <li class="py-1">
                <a href="asset_movements.php" class="nav-link <?= checkActiveMenu('asset_movements.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                    <i class="bi bi-arrow-left-right me-2"></i> Asset Movements
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</li>
<?php endif; ?>
<!-- 8, 9, 10. GRUP NETWORK & SERVER -->
<?php if (hasMenuAccess('server.php', $userRole, $menuPermissions) || hasMenuAccess('network_device.php', $userRole, $menuPermissions) || hasMenuAccess('network_port.php', $userRole, $menuPermissions)): ?>
<li class="nav-item mb-1">
    <a href="#networkSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $isNetworkGroupActive ? 'true' : 'false' ?>" 
       class="nav-link <?= $isNetworkGroupActive ? 'active-style' : '' ?> rounded d-flex align-items-center justify-content-between">
        <div><i class="bi bi-hdd-network me-3"></i> Network</div>
        <i class="bi bi-chevron-down small"></i>
    </a>
    <div class="collapse <?= $isNetworkGroupActive ? 'show' : '' ?>" id="networkSubmenu">
        <ul class="list-unstyled ps-4 my-1">
            <?php if (hasMenuAccess('server.php', $userRole, $menuPermissions)): ?>
            <li class="py-1">
                <a href="server.php" class="nav-link <?= checkActiveMenu('server.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                    <i class="bi bi-server me-2"></i> Server
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasMenuAccess('network_device.php', $userRole, $menuPermissions)): ?>
            <li class="py-1">
                <a href="network_device.php" class="nav-link <?= checkActiveMenu('network_device.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                    <i class="bi bi-cpu me-2"></i> Network Device
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasMenuAccess('network_port.php', $userRole, $menuPermissions)): ?>
            <li class="py-1">
                <a href="network_port.php" class="nav-link <?= checkActiveMenu('network_port.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                    <i class="bi bi-ethernet me-2"></i> Network Port
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</li>
<?php endif; ?>
<!-- 11. VENDORS -->
<?php if (hasMenuAccess('vendors.php', $userRole, $menuPermissions)): ?>
<li class="nav-item mb-1">
    <a href="vendors.php" class="nav-link <?= checkActiveMenu('vendors.php', $currentFile) ?> rounded d-flex align-items-center">
        <i class="bi bi-shop me-3"></i> Vendors
    </a>
</li>
<?php endif; ?>
<!-- 12, 13. GRUP PASSWORD -->
<?php if (hasMenuAccess('password_categories.php', $userRole, $menuPermissions) || hasMenuAccess('password_vault.php', $userRole, $menuPermissions)): ?>
<li class="nav-item mb-1">
    <a href="#passwordSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $isPasswordGroupActive ? 'true' : 'false' ?>" 
       class="nav-link <?= $isPasswordGroupActive ? 'active-style' : '' ?> rounded d-flex align-items-center justify-content-between">
        <div><i class="bi bi-key me-3"></i> Password</div>
        <i class="bi bi-chevron-down small"></i>
    </a>
    <div class="collapse <?= $isPasswordGroupActive ? 'show' : '' ?>" id="passwordSubmenu">
        <ul class="list-unstyled ps-4 my-1">
            <?php if (hasMenuAccess('password_categories.php', $userRole, $menuPermissions)): ?>
            <li class="py-1">
                <a href="password_categories.php" class="nav-link <?= checkActiveMenu('password_categories.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                    <i class="bi bi-folder-symlink me-2"></i> Password Categories
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasMenuAccess('password_vault.php', $userRole, $menuPermissions)): ?>
            <li class="py-1">
                <a href="password_vault.php" class="nav-link <?= checkActiveMenu('password_vault.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                    <i class="bi bi-lock me-2"></i> Password Vault
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</li>
<?php endif; ?>
<!-- 14. TIKETS -->
<?php if (hasMenuAccess('tickets.php', $userRole, $menuPermissions)): ?>
<li class="nav-item mb-1">
    <a href="tickets.php" class="nav-link <?= checkActiveMenu('tickets.php', $currentFile) ?> rounded d-flex align-items-center">
        <i class="bi bi-ticket-perforated me-3"></i> Tikets
    </a>
</li>
<?php endif; ?>
<!-- 15. MAINTENANCE -->
<?php if (hasMenuAccess('maintenance.php', $userRole, $menuPermissions)): ?>
<li class="nav-item mb-1">
    <a href="maintenance.php" class="nav-link <?= checkActiveMenu('maintenance.php', $currentFile) ?> rounded d-flex align-items-center">
        <i class="bi bi-wrench-adjustable me-3"></i> Maintenance
    </a>
</li>
<?php endif; ?>
<!-- 16, 17. GRUP KNOWLEDGE / ARTIKEL -->
<?php if (hasMenuAccess('knowledge_categories.php', $userRole, $menuPermissions) || hasMenuAccess('knowledge_articles.php', $userRole, $menuPermissions)): ?>
<li class="nav-item mb-1">
    <a href="#knowledgeSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $isKnowledgeGroupActive ? 'true' : 'false' ?>" 
       class="nav-link <?= $isKnowledgeGroupActive ? 'active-style' : '' ?> rounded d-flex align-items-center justify-content-between">
        <div><i class="bi bi-journal-text me-3"></i> Artikel</div>
        <i class="bi bi-chevron-down small"></i>
    </a>
    <div class="collapse <?= $isKnowledgeGroupActive ? 'show' : '' ?>" id="knowledgeSubmenu">
        <ul class="list-unstyled ps-4 my-1">
            <?php if (hasMenuAccess('knowledge_categories.php', $userRole, $menuPermissions)): ?>
            <li class="py-1">
                <a href="knowledge_categories.php" class="nav-link <?= checkActiveMenu('knowledge_categories.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                    <i class="bi bi-tags me-2"></i> Knowledge Categories
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasMenuAccess('knowledge_articles.php', $userRole, $menuPermissions)): ?>
            <li class="py-1">
                <a href="knowledge_articles.php" class="nav-link <?= checkActiveMenu('knowledge_articles.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                    <i class="bi bi-file-earmark-text me-2"></i> Knowledge Articles
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</li>
<?php endif; ?>
<!-- 18, 19. GRUP SOP -->
<?php if (hasMenuAccess('sop_categories.php', $userRole, $menuPermissions) || hasMenuAccess('sops.php', $userRole, $menuPermissions)): ?>
<li class="nav-item mb-1">
    <a href="#sopSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $isSopGroupActive ? 'true' : 'false' ?>" 
       class="nav-link <?= $isSopGroupActive ? 'active-style' : '' ?> rounded d-flex align-items-center justify-content-between">
        <div><i class="bi bi-file-check me-3"></i> SOP</div>
        <i class="bi bi-chevron-down small"></i>
    </a>
    <div class="collapse <?= $isSopGroupActive ? 'show' : '' ?>" id="sopSubmenu">
        <ul class="list-unstyled ps-4 my-1">
            <?php if (hasMenuAccess('sop_categories.php', $userRole, $menuPermissions)): ?>
            <li class="py-1">
                <a href="sop_categories.php" class="nav-link <?= checkActiveMenu('sop_categories.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                    <i class="bi bi-bookmark me-2"></i> SOP Categories
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasMenuAccess('sops.php', $userRole, $menuPermissions)): ?>
            <li class="py-1">
                <a href="sops.php" class="nav-link <?= checkActiveMenu('sops.php', $currentFile) ?> rounded small py-1 px-2 d-flex align-items-center">
                    <i class="bi bi-file-earmark-check me-2"></i> SOPS
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</li>
<?php endif; ?>
<!-- 20. SOFTWARE LICENSES -->
<?php if (hasMenuAccess('software_licenses.php', $userRole, $menuPermissions)): ?>
<li class="nav-item mb-1">
    <a href="software_licenses.php" class="nav-link <?= checkActiveMenu('software_licenses.php', $currentFile) ?> rounded d-flex align-items-center">
        <i class="bi bi-patch-check me-3"></i> Software Licenses
    </a>
</li>
<?php endif; ?>
<!-- 21. BACKUP JOBS -->
<?php if (hasMenuAccess('backup_jobs.php', $userRole, $menuPermissions)): ?>
<li class="nav-item mb-1">
    <a href="backup_jobs.php" class="nav-link <?= checkActiveMenu('backup_jobs.php', $currentFile) ?> rounded d-flex align-items-center">
        <i class="bi bi-cloud-arrow-up me-3"></i> Backup Jobs
    </a>
</li>
<?php endif; ?>
<!-- 22. DAILY CHECKLIST -->
<?php if (hasMenuAccess('daily_checklist.php', $userRole, $menuPermissions)): ?>
<li class="nav-item mb-1">
    <a href="daily_checklist.php" class="nav-link <?= checkActiveMenu('daily_checklist.php', $currentFile) ?> rounded d-flex align-items-center">
        <i class="bi bi-card-checklist me-3"></i> Daily Checklist
    </a>
</li>
<?php endif; ?>
<!-- 23. ACTIVITY LOGS -->
<?php if (hasMenuAccess('activity_logs.php', $userRole, $menuPermissions)): ?>
<li class="nav-item mb-1">
    <a href="activity_logs.php" class="nav-link <?= checkActiveMenu('activity_logs.php', $currentFile) ?> rounded d-flex align-items-center">
        <i class="bi bi-clock-history me-3"></i> Activity Logs
    </a>
</li>
<?php endif; ?>
    </ul>
</div> <!-- Penutup elemen .menu-scroll-container -->

<!-- KOTAK LOGOUT MENETAP DI DASAR PALING BAWAH SIDEBAR -->
<div class="w-100 pt-2 mt-2 border-top border-secondary logout-fixed-box" 
     style="background-color: #212529 !important; position: relative !important; bottom: 0 !important; z-index: 1050 !important;">
    <a href="logout.php" 
       class="nav-link rounded d-flex align-items-center py-2 px-3 fw-bold text-decoration-none"
       style="color: #dc3545 !important; transition: all 0.2s ease-in-out;"
       onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem ITAKMS?')">
        <i class="bi bi-box-arrow-left me-3 fs-5"></i> Logout
    </a>
</div>
