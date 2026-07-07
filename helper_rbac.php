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
            if ($role_id === 2) return true;
            if ($role_id === 3 && in_array($action, ['C', 'R', 'U'])) return true; // Teknisi: Tambah, Read, Update
            if ($role_id === 4 && $action === 'R') return true;
            return false;

        // Modul: Password Vault
        case 'password_vault':
        case 'password_categories':
        case 'password_histories':
            if ($role_id === 2) return true;
            if ($role_id === 3 && $action === 'R') return true; // Teknisi: Read* (Terbatasi pekerjaan)
            return false;

        // Modul: Maintenance & SOP & Software License
        case 'maintenance':
        case 'sop_categories':
        case 'software_licenses':
            if ($role_id === 2) return true;
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
        echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>";
        echo "<h2>403 - Akses Ditolak</h2>";
        echo "<p>Role Anda tidak memiliki izin untuk memproses data pada tabel <strong>$table_name</strong> ($action).</p>";
        echo "<a href='dashboard.php'>Kembali ke Dashboard</a>";
        echo "</div>";
        exit;
    }
}
