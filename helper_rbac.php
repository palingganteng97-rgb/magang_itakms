<?php
// Pastikan session sudah berjalan di auth.php Anda
// Asumsi: $_SESSION['user_role_id'] menyimpan ID dari tabel roles (1, 2, 3, atau 4)

/**
 * Memeriksa hak akses berdasarkan role_id dan nama tabel database fisik.
 * 
 * @param string $table_name Nama tabel sesuai di HeidiSQL (ex: 'assets', 'backup_jobs')
 * @param string $action Tindakan yang ingin dilakukan ('C' = Create, 'R' = Read, 'U' = Update, 'D' = Delete, 'Master' = Lihat Master Data)
 * @return bool
 */
function check_access_by_table($table_name, $action = 'R') {
    if (!isset($_SESSION['user_role_id'])) {
        return false;
    }

    $role_id = (int)$_SESSION['user_role_id'];

    // ID 1: SUPER ADMIN -> Akses mutlak tanpa batas
    if ($role_id === 1) {
        return true;
    }

    // Pemetaan logika matriks berdasarkan nama tabel di database Anda
    switch ($table_name) {
        
        // Modul: User & Role Management (Hanya Super Admin)
        case 'users':
        case 'roles':
            return false;

        // Modul: Gedung/Lantai/Ruangan
        case 'buildings':
        case 'floors':
        case 'rooms':
            if ($role_id === 2) return true; // Admin IT (Full)
            if (($role_id === 3 || $role_id === 4) && $action === 'Master') return true; // Teknisi & Viewer (Mata 👀)
            return false;

        // Modul: Kategori, Brand, Status Asset
        case 'asset_categories':
        case 'asset_brands':
        case 'asset_statuses':
            if ($role_id === 2) return true;
            if (($role_id === 3 || $role_id === 4) && $action === 'Master') return true; // Mata 👀
            return false;

        // Modul: Asset & Upload Foto Asset
        case 'assets':
        case 'asset_images':
            if ($role_id === 2) return true;
            if ($role_id === 3 && in_array($action, ['R', 'U'])) return true; // Teknisi: Update Status & Read
            if ($role_id === 4 && $action === 'R') return true; // Viewer: Read
            return false;

        // Modul: Riwayat Perpindahan, Server, Network Device/Ports
        case 'asset_movements':
        case 'servers':
        case 'network_devices':
        case 'network_port':
        case 'network_ports':
            if ($role_id === 2) return true;
            if ($role_id === 3 && in_array($action, ['C', 'R', 'U'])) return true; // Teknisi: Tambah, Read, Update
            if ($role_id === 4 && $action === 'R') return true;
            return false;

        // Modul: Password Vault
        case 'password_vault':
        case 'password_vaults':
        case 'password_categories':
        case 'password_histories':
            if ($role_id === 2) return true;
            if ($role_id === 3 && $action === 'R') return true; // Teknisi: Read* (Terbatasi pekerjaan)
            return false;

        // Modul: Maintenance & SOP & Software License
        case 'maintenance':
        case 'maintenance_logs':
        case 'sop_categories':
        case 'sops': // FIXED: Case ditambahkan agar halaman sops.php tidak terblokir lagi
        case 'software_licenses':
            if ($role_id === 2) return true; // Admin IT (Full)
            if (in_array($role_id, [3, 4]) && $action === 'R') return true; // Teknisi & Viewer: Read
            return false;

        // Modul: Backup Monitoring
        case 'backup_jobs':
            if ($role_id === 2) return true;
            if ($role_id === 3 && in_array($action, ['R', 'U'])) return true; // Teknisi: Update Status & Read
            if ($role_id === 4 && $action === 'R') return true;
            return false;

        // Modul: Daily Checklist
        case 'daily_checklists':
            if ($role_id === 2) return true;
            if ($role_id === 3 && in_array($action, ['C', 'R', 'U'])) return true; // Teknisi: Isi Checklist & Read
            if ($role_id === 4 && $action === 'R') return true;
            return false;

        // Modul: Knowledge Base
        case 'knowledge_articles':
        case 'knowledge_categories':
            if ($role_id === 2) return true;
            if ($role_id === 3 && in_array($action, ['C', 'R', 'U'])) return true; // Teknisi: Tambah/Edit & Read
            if ($role_id === 4 && $action === 'R') return true;
            return false;

        // Modul: Notification
        case 'notifications':
            if ($role_id === 2) return true;
            if (in_array($role_id, [3, 4]) && $action === 'R') return true;
            return false;

        // Modul: Activity Log
        case 'activity_logs':
            if ($role_id === 2 && $action === 'R') return true; // Hanya Admin IT yang bisa Read
            return false;

        // Tambahan Mandiri untuk Modul Ticket (Jika nanti ditambahkan ke DB)
        case 'tickets':
        case 'ticket_comments':
            if ($role_id === 2 || $role_id === 3) return true; // Admin IT & Teknisi CRUD penuh
            if ($role_id === 4 && in_array($action, ['C', 'R'])) return true; // Viewer: Buat & Read punya sendiri
            return false;

        default:
            return false; // Tolak akses jika tabel tidak terdaftar
    }
}

/**
 * Interseptor halaman untuk memblokir akses ilegal langsung di baris paling atas controller PHP.
 */
function protect_page_by_table($table_name, $action = 'R') {
    if (!check_access_by_table($table_name, $action)) {
        header('HTTP/1.1 403 Forbidden');
        
        // Translasi kode action menjadi teks yang ramah dibaca user
        $action_labels = [
            'C' => 'menambahkan data baru (Create)',
            'R' => 'melihat daftar data (Read)',
            'U' => 'mengubah/memperbarui data (Update)',
            'D' => 'menghapus berkas data (Delete)',
            'Master' => 'mengintip komponen master data'
        ];
        $tindakan_teks = $action_labels[$action] ?? 'memproses data';
        
        // Mengubah teks snake_case nama tabel menjadi Capital Case (ex: backup_jobs -> Backup Jobs)
        $nama_modul = ucwords(str_replace('_', ' ', $table_name));
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

        <body class="d-flex align-items-center justify-content-center min-vh-100 p-3">

            <div class="error-card text-center p-5">
                <!-- Shield Icon Animation -->
                <div class="icon-box mb-4">
                    <i class="bi bi-shield-slash-fill"></i>
                </div>
                
                <!-- Error Code & Header -->
                <h1 class="fw-bold text-danger mb-2">403</h1>
                <h3 class="fw-bold fs-5 text-dark mb-3">Akses Ditolak / Forbidden</h3>
                
                <!-- Info Dinamis Sesuai Tabel & Aksi -->
                <p class="text-secondary small mb-4 px-2" style="line-height: 1.6;">
                    Maaf, peran akun Anda saat ini tidak memiliki kewenangan khusus untuk <strong><?= $tindakan_teks; ?></strong> pada modul <strong><?= $nama_modul; ?></strong>.
                </p>
                
                <!-- Tombol Navigasi Kembalian -->
                <div class="d-grid gap-2">
                    <a href="dashboard.php" class="btn btn-primary btn-sm py-2 fw-medium rounded-3">
                        <i class="bi bi-house-door me-2"></i>Kembali ke Dashboard
                    </a>
                </div>
            </div>

        </body>
        </html>
        <?php
        exit;
    }
}
